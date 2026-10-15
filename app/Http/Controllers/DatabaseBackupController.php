<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

class DatabaseBackupController extends Controller
{
    /**
     * Download the live database backup as a compressed ZIP file safely.
     * Guaranteed zero-lock, read-only, non-blocking on live production data.
     *
     * @param Request $request
     * @return BinaryFileResponse|\Illuminate\Http\JsonResponse
     */
    public function downloadDatabaseZip(Request $request)
    {
        // How to Use on Live
        // Logged in as Super Admin: Visit /download-database-zip in your browser.
        // Automated/External script: Add DB_BACKUP_SECRET=your_secret_passphrase to your .env and visit:
        // https://your-domain.com/download-database-zip?token=your_secret_passphrase

        // 1. Authorization check for live production safety
        if (!$this->isAuthorized($request)) {
            abort(403, 'Unauthorized access. Please log in as Super Admin or provide a valid backup secret token (?token=...).');
        }

        // 2. Concurrency lock to shield the live server from simultaneous heavy dumps
        $lock = Cache::lock('db_backup_process_lock', 600);
        if (!$lock->get()) {
            return response()->json([
                'status' => 'busy',
                'message' => 'Another backup process is currently in progress. Please wait a few moments and try again.',
            ], 429);
        }

        // Increase limits safely for background streaming
        @set_time_limit(600);
        @ini_set('memory_limit', '512M');

        $backupDir = storage_path('app/backups');
        if (!file_exists($backupDir)) {
            @mkdir($backupDir, 0755, true);
        }

        // Clean up stale backup files older than 30 minutes from aborted connections
        $this->cleanupOldBackups($backupDir);

        // Pre-flight disk space check (ensure at least 100MB free disk space)
        $freeDisk = @disk_free_space($backupDir);
        if ($freeDisk !== false && $freeDisk < 100 * 1024 * 1024) {
            $lock->release();
            return response()->json([
                'status' => 'error',
                'message' => 'Insufficient server disk space to safely generate database backup.',
            ], 507);
        }

        $dbConnection = config('database.default', 'mysql');
        $dbConfig = config("database.connections.{$dbConnection}");

        $database = $dbConfig['database'] ?? 'database';
        $timestamp = date('Y-m-d_H-i-s');
        $sqlFileName = "{$database}_{$timestamp}.sql";
        $zipFileName = "{$database}_{$timestamp}.zip";

        $sqlPath = $backupDir . DIRECTORY_SEPARATOR . $sqlFileName;
        $zipPath = $backupDir . DIRECTORY_SEPARATOR . $zipFileName;

        try {
            // Step 1: Dump database to SQL file using non-blocking, consistent snapshot read
            $dumpSuccess = $this->dumpDatabase($dbConfig, $sqlPath);

            if (!$dumpSuccess || !file_exists($sqlPath) || filesize($sqlPath) === 0) {
                throw new \Exception("Database dump could not be completed or the generated file is empty.");
            }

            // Step 2: Compress SQL into a ZIP archive
            if (!class_exists('ZipArchive')) {
                throw new \Exception("PHP ZipArchive extension is not enabled on this server.");
            }

            $zip = new ZipArchive();
            $zipStatus = $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
            if ($zipStatus !== true) {
                throw new \Exception("Failed to initialize ZIP archive (code {$zipStatus}).");
            }

            $zip->addFile($sqlPath, $sqlFileName);
            $zip->close();

            // Immediately delete the uncompressed SQL file to free disk space
            if (file_exists($sqlPath)) {
                @unlink($sqlPath);
            }

            // Clear any stray cleanable output buffers to guarantee binary archive integrity
            while (ob_get_level() > 0) {
                $obStatus = ob_get_status();
                if (!empty($obStatus) && isset($obStatus['flags']) && !($obStatus['flags'] & PHP_OUTPUT_HANDLER_CLEANABLE)) {
                    break;
                }
                if (!@ob_end_clean()) {
                    break;
                }
            }

            // Release lock before streaming file to client
            $lock->release();

            // Step 3: Stream download to client and delete zip file automatically after sending
            return response()->download($zipPath, $zipFileName, [
                'Content-Type' => 'application/zip',
                'Content-Disposition' => 'attachment; filename="' . $zipFileName . '"',
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0',
            ])->deleteFileAfterSend(true);

        } catch (\Throwable $e) {
            $lock->release();

            Log::error("Live database backup failed: " . $e->getMessage(), [
                'exception' => $e,
            ]);

            if (file_exists($sqlPath)) {
                @unlink($sqlPath);
            }
            if (file_exists($zipPath)) {
                @unlink($zipPath);
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to generate live database backup: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Perform database dump safely without table locking.
     * Tries native mysqldump first, falling back to pure PHP PDO dumper.
     *
     * @param array $dbConfig
     * @param string $sqlPath
     * @return bool
     */
    protected function dumpDatabase(array $dbConfig, string $sqlPath): bool
    {
        $mysqldumpPath = $this->locateMysqldumpBinary();

        if ($mysqldumpPath) {
            try {
                $args = $this->buildMysqldumpArgs($mysqldumpPath, $dbConfig, $sqlPath);
                $process = Process::timeout(600)->run($args);

                if ($process->successful() && file_exists($sqlPath) && filesize($sqlPath) > 0) {
                    return true;
                }

                Log::warning("mysqldump process exited with non-zero status (" . $process->exitCode() . "): " . $process->errorOutput());
            } catch (\Throwable $ex) {
                Log::warning("mysqldump execution threw exception: " . $ex->getMessage());
            }
        }

        // Safe fallback: pure PHP PDO dump with REPEATABLE READ snapshot & unbuffered streaming
        Log::info("Falling back to safe pure PHP PDO database dump...");
        $this->dumpWithPhp($dbConfig, $sqlPath);
        return file_exists($sqlPath) && filesize($sqlPath) > 0;
    }

    /**
     * Build mysqldump command arguments tailored for safe, non-locking live dumps.
     *
     * @param string $mysqldumpPath
     * @param array $dbConfig
     * @param string $sqlPath
     * @return array
     */
    protected function buildMysqldumpArgs(string $mysqldumpPath, array $dbConfig, string $sqlPath): array
    {
        $host = $dbConfig['host'] ?? '127.0.0.1';
        $port = $dbConfig['port'] ?? '3306';
        $username = $dbConfig['username'] ?? 'root';
        $password = $dbConfig['password'] ?? '';
        $database = $dbConfig['database'] ?? '';

        $helpText = @shell_exec('"' . $mysqldumpPath . '" --help 2>&1') ?: '';

        $args = [
            $mysqldumpPath,
            "--host={$host}",
            "--port={$port}",
            "--user={$username}",
        ];

        // 1. Consistent snapshot without locking InnoDB tables (Zero blocking on live transactions)
        if (str_contains($helpText, '--single-transaction')) {
            $args[] = '--single-transaction';
        }

        // 2. Explicitly avoid acquiring read/write locks on tables
        if (str_contains($helpText, '--skip-lock-tables')) {
            $args[] = '--skip-lock-tables';
        }

        // 3. Stream rows one by one from MySQL server to prevent RAM spikes
        if (str_contains($helpText, '--quick')) {
            $args[] = '--quick';
        }

        // 4. Preserve accurate utf8mb4 encoding without data corruption
        $args[] = '--default-character-set=utf8mb4';

        // 5. Avoid requiring PROCESS privilege on MySQL 8.0+
        if (str_contains($helpText, '--no-tablespaces')) {
            $args[] = '--no-tablespaces';
        }

        // 6. Disable column statistics if supported (avoids MySQL 8+ permission/information_schema errors)
        if (str_contains($helpText, '--column-statistics')) {
            $args[] = '--column-statistics=0';
        }

        // 7. Avoid GTID replication errors on cloud/live servers
        if (str_contains($helpText, '--set-gtid-purged')) {
            $args[] = '--set-gtid-purged=OFF';
        }

        // 8. Avoid LOCK TABLES statements in the dump file
        if (str_contains($helpText, '--skip-add-locks')) {
            $args[] = '--skip-add-locks';
        }

        if (!empty($password)) {
            $args[] = "--password={$password}";
        }

        $args[] = $database;
        $args[] = "--result-file={$sqlPath}";

        return $args;
    }

    /**
     * Pure PHP PDO database dumper as a zero-dependency, non-locking fallback.
     * Uses REPEATABLE READ snapshot & unbuffered row streaming (constant minimal RAM).
     *
     * @param array $dbConfig
     * @param string $sqlPath
     * @return void
     * @throws \Exception
     */
    protected function dumpWithPhp(array $dbConfig, string $sqlPath): void
    {
        $host = $dbConfig['host'] ?? '127.0.0.1';
        $port = $dbConfig['port'] ?? '3306';
        $database = $dbConfig['database'] ?? '';
        $username = $dbConfig['username'] ?? 'root';
        $password = $dbConfig['password'] ?? '';

        $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";
        $pdo = new \PDO($dsn, $username, $password, [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => false, // Stream rows one-by-one from MySQL
        ]);

        // Start consistent snapshot read transaction (ZERO table locking for InnoDB)
        $pdo->exec("SET TRANSACTION ISOLATION LEVEL REPEATABLE READ;");
        $pdo->beginTransaction();

        $handle = fopen($sqlPath, 'w');
        if (!$handle) {
            $pdo->rollBack();
            throw new \Exception("Cannot open file for writing: {$sqlPath}");
        }

        try {
            fwrite($handle, "-- --------------------------------------------------------\n");
            fwrite($handle, "-- Live Database Backup (Safe Snapshot)\n");
            fwrite($handle, "-- Database: `{$database}`\n");
            fwrite($handle, "-- Generated: " . date('Y-m-d H:i:s') . "\n");
            fwrite($handle, "-- --------------------------------------------------------\n\n");
            fwrite($handle, "SET FOREIGN_KEY_CHECKS=0;\n");
            fwrite($handle, "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n");
            fwrite($handle, "SET AUTOCOMMIT = 0;\n");
            fwrite($handle, "START TRANSACTION;\n");
            fwrite($handle, "SET time_zone = \"+00:00\";\n\n");

            // Fetch table list
            $stmt = $pdo->query('SHOW FULL TABLES WHERE Table_type = "BASE TABLE"');
            $tables = $stmt->fetchAll(\PDO::FETCH_COLUMN);

            foreach ($tables as $table) {
                // Table DDL structure
                fwrite($handle, "-- --------------------------------------------------------\n");
                fwrite($handle, "-- Table structure for `{$table}`\n");
                fwrite($handle, "-- --------------------------------------------------------\n\n");
                fwrite($handle, "DROP TABLE IF EXISTS `{$table}`;\n");

                $createStmt = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch();
                $createTableSql = $createStmt['Create Table'] ?? array_values($createStmt)[1];
                fwrite($handle, $createTableSql . ";\n\n");

                // Table Data (strictly non-locking SELECT)
                fwrite($handle, "-- Dumping data for table `{$table}`\n\n");
                $query = $pdo->query("SELECT * FROM `{$table}`");
                if ($query) {
                    $batchSize = 250;
                    $rows = [];
                    $columns = [];
                    $firstRow = true;

                    while ($row = $query->fetch(\PDO::FETCH_ASSOC)) {
                        if ($firstRow) {
                            $columns = array_map(function ($col) {
                                return "`" . str_replace("`", "``", $col) . "`";
                            }, array_keys($row));
                            $firstRow = false;
                        }

                        $values = array_map(function ($val) use ($pdo) {
                            if (is_null($val)) {
                                return 'NULL';
                            }
                            return $pdo->quote($val);
                        }, $row);

                        $rows[] = '(' . implode(', ', $values) . ')';

                        if (count($rows) >= $batchSize) {
                            fwrite($handle, "INSERT INTO `{$table}` (" . implode(', ', $columns) . ") VALUES\n" . implode(",\n", $rows) . ";\n\n");
                            $rows = [];
                        }
                    }

                    if (!empty($rows)) {
                        fwrite($handle, "INSERT INTO `{$table}` (" . implode(', ', $columns) . ") VALUES\n" . implode(",\n", $rows) . ";\n\n");
                    }
                }
                fwrite($handle, "\n");
            }

            // Views
            $stmtViews = $pdo->query('SHOW FULL TABLES WHERE Table_type = "VIEW"');
            $views = $stmtViews->fetchAll(\PDO::FETCH_COLUMN);

            foreach ($views as $view) {
                fwrite($handle, "-- --------------------------------------------------------\n");
                fwrite($handle, "-- View structure for `{$view}`\n");
                fwrite($handle, "-- --------------------------------------------------------\n\n");
                fwrite($handle, "DROP VIEW IF EXISTS `{$view}`;\n");
                $createStmt = $pdo->query("SHOW CREATE VIEW `{$view}`")->fetch();
                $createViewSql = $createStmt['Create View'] ?? array_values($createStmt)[1];
                fwrite($handle, $createViewSql . ";\n\n");
            }

            fwrite($handle, "COMMIT;\n");
            fwrite($handle, "SET FOREIGN_KEY_CHECKS=1;\n");
        } finally {
            fclose($handle);
            // Complete the read transaction
            $pdo->commit();
            $pdo = null;
        }
    }

    /**
     * Find mysqldump executable path across Windows & Linux locations.
     *
     * @return string|null
     */
    protected function locateMysqldumpBinary(): ?string
    {
        // 1. Explicit env or config variable
        $customPath = env('MYSQLDUMP_PATH', config('database.connections.mysql.dump_path'));
        if ($customPath && file_exists($customPath)) {
            return $customPath;
        }

        // 2. Check system PATH
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $whereCheck = @shell_exec('where mysqldump.exe 2>NUL');
            if ($whereCheck) {
                $lines = array_filter(array_map('trim', explode("\n", $whereCheck)));
                foreach ($lines as $line) {
                    if (file_exists($line)) {
                        return $line;
                    }
                }
            }
        } else {
            $whichCheck = @shell_exec('which mysqldump 2>/dev/null') ?: @shell_exec('which mariadb-dump 2>/dev/null');
            if ($whichCheck) {
                $path = trim($whichCheck);
                if (file_exists($path)) {
                    return $path;
                }
            }
        }

        // 3. Common paths on Linux / Unix servers
        $linuxCandidates = [
            '/usr/bin/mysqldump',
            '/usr/local/bin/mysqldump',
            '/usr/bin/mariadb-dump',
            '/usr/local/mysql/bin/mysqldump',
        ];
        foreach ($linuxCandidates as $candidate) {
            if (file_exists($candidate) && is_executable($candidate)) {
                return $candidate;
            }
        }

        // 4. Common paths on Windows (WAMP / XAMPP / Laragon / MySQL Server)
        $windowsCandidates = [
            'D:/wamp64/bin/mysql/mysql*/bin/mysqldump.exe',
            'C:/wamp64/bin/mysql/mysql*/bin/mysqldump.exe',
            'D:/xampp/mysql/bin/mysqldump.exe',
            'C:/xampp/mysql/bin/mysqldump.exe',
            'C:/laragon/bin/mysql/mysql*/bin/mysqldump.exe',
            'C:/Program Files/MySQL/MySQL Server */bin/mysqldump.exe',
            'C:/Program Files (x86)/MySQL/MySQL Server */bin/mysqldump.exe',
        ];

        foreach ($windowsCandidates as $pattern) {
            $matches = glob($pattern);
            if (!empty($matches)) {
                rsort($matches);
                foreach ($matches as $match) {
                    if (file_exists($match)) {
                        return $match;
                    }
                }
            }
        }

        return null;
    }

    /**
     * Check authorization before allowing live database backup download.
     *
     * @param Request $request
     * @return bool
     */
    protected function isAuthorized(Request $request): bool
    {
        // 1. Local environment permits direct access for development
        if (app()->isLocal()) {
            return true;
        }

        // 2. Secret token check via query param (?token=... or ?secret=... or ?key=...) or header (X-Backup-Secret)
        $configuredSecret = env('DB_BACKUP_SECRET');
        if (!empty($configuredSecret)) {
            $providedSecret = $request->input('token')
                ?? $request->input('secret')
                ?? $request->input('key')
                ?? $request->header('X-Backup-Secret');

            if (!empty($providedSecret) && hash_equals((string) $configuredSecret, (string) $providedSecret)) {
                return true;
            }
        }

        // 3. Authenticated session check
        if (auth()->check()) {
            $user = auth()->user();
            if ($user->user_type === 'super-admin') {
                return true;
            }
            if (method_exists($user, 'hasRole') && ($user->hasRole('Admin') || $user->hasRole('Super Admin') || $user->hasRole('super-admin'))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Clean up backup files older than 30 minutes in the backup directory.
     *
     * @param string $backupDir
     * @return void
     */
    protected function cleanupOldBackups(string $backupDir): void
    {
        try {
            $files = glob($backupDir . DIRECTORY_SEPARATOR . '*.*');
            $now = time();
            foreach ($files as $file) {
                if (is_file($file) && ($now - filemtime($file) > 1800)) {
                    @unlink($file);
                }
            }
        } catch (\Throwable $e) {
            // Silently ignore cleanup issues
        }
    }
}
