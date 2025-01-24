<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class SetupInitialApplication extends Command
{
    protected $signature = 'app:setup';
    protected $description = 'Setup the application by creating required folders, deleting public/storage, and running migrations and seeds.';

    // Variables to track changes
    protected $foldersCreated = [];
    protected $envUpdated = false;
    protected $migrationsRun = false;

    public function handle()
    {
        try {
            // Step 1: Check and create required folders
            $folders = [
                storage_path('framework/cache'),
                storage_path('framework/sessions'),
                storage_path('framework/views'),
            ];

            foreach ($folders as $folder) {
                if (!File::exists($folder)) {
                    File::makeDirectory($folder, 0755, true);
                    $this->foldersCreated[] = $folder; // Track created folders
                    $this->info("Created folder: $folder");
                } else {
                    $this->info("Folder already exists: $folder");
                }
            }

            // Step 2: Delete public/storage if it exists
            $publicStorage = public_path('storage');
            if (File::exists($publicStorage)) {
                File::deleteDirectory($publicStorage);
                $this->info("Deleted public/storage folder.");
            } else {
                $this->info("public/storage folder does not exist.");
            }

            // Step 3: Copy .env.example to .env if it doesn't exist
            if (!File::exists(base_path('.env'))) {
                File::copy(base_path('.env.example'), base_path('.env'));
                $this->info(".env file created successfully.");
            } else {
                $this->info(".env file already exists.");
            }

            // Step 4: Show default settings and ask for confirmation
            $this->info("Default database configuration:");
            $this->line("DB_CONNECTION=mysql");
            $this->line("DB_HOST=127.0.0.1");
            $this->line("DB_PORT=3306");
            $this->line("DB_DATABASE=meskay");
            $this->line("DB_USERNAME=root");
            $this->line("DB_PASSWORD=");

            $useDefaultSettings = $this->confirm('Do you want to proceed with these default settings?', true);

            if (!$useDefaultSettings) {
                // Step 5: Ask for custom database configuration
                $this->info("Please provide your custom database configuration:");

                $dbConnection = $this->ask('Database Connection (e.g., mysql):', 'mysql');
                $dbHost = $this->ask('Database Host:', '127.0.0.1');
                $dbPort = $this->ask('Database Port:', '3306');
                $dbDatabase = $this->ask('Database Name:', 'meskay');
                $dbUsername = $this->ask('Database Username:', 'root');
                $dbPassword = $this->ask('Database Password:', '');

                // Update .env file with custom database configuration
                $envContent = file_get_contents(base_path('.env'));
                $envContent = preg_replace('/DB_CONNECTION=(.*)/', "DB_CONNECTION=$dbConnection", $envContent);
                $envContent = preg_replace('/DB_HOST=(.*)/', "DB_HOST=$dbHost", $envContent);
                $envContent = preg_replace('/DB_PORT=(.*)/', "DB_PORT=$dbPort", $envContent);
                $envContent = preg_replace('/DB_DATABASE=(.*)/', "DB_DATABASE=$dbDatabase", $envContent);
                $envContent = preg_replace('/DB_USERNAME=(.*)/', "DB_USERNAME=$dbUsername", $envContent);
                $envContent = preg_replace('/DB_PASSWORD=(.*)/', "DB_PASSWORD=$dbPassword", $envContent);
                file_put_contents(base_path('.env'), $envContent);

                $this->envUpdated = true; // Track .env file update
                $this->info("Custom database configuration updated successfully.");
            }

            // Step 6: Generate application key
            $this->call('key:generate');
            $this->info("Application key generated successfully.");

            // Step 7: Ask for final confirmation
            $confirmation = $this->confirm('Are you sure you want to proceed? This will truncate all your initial data & Storages. Type "yes" to confirm.');

            if ($confirmation) {
                // Step 8: Generate a random number for second confirmation
                $randomNumber = rand(100000, 999999); // Generate a random 6-digit number
                $userInput = $this->ask("Are you sure you want to continue? To proceed, please type the following number: $randomNumber");

                if ($userInput == $randomNumber) {
                    // Step 9: Run migrations and seeds
                    $this->call('migrate:fresh');
                    $this->migrationsRun = true; // Track migrations
                    $this->call('db:seed');
                    $this->call('storage:link');

                    $this->info('Application setup completed successfully.');
                } else {
                    $this->error('Incorrect number. Setup aborted.');
                    $this->rollback();
                }
            } else {
                $this->info('Setup aborted.');
                $this->rollback();
            }
        } catch (\Exception $e) {
            $this->error('An error occurred: ' . $e->getMessage());
            $this->rollback();
        }
    }

    protected function rollback()
    {
        $this->info('Starting rollback...');

        // Rollback migrations if they were run
        if ($this->migrationsRun) {
            $this->call('migrate:rollback');
            $this->info('Migrations rolled back successfully.');
        }

        // Delete created folders
        foreach ($this->foldersCreated as $folder) {
            if (File::exists($folder)) {
                File::deleteDirectory($folder);
                $this->info("Deleted folder: $folder");
            }
        }

        // Restore .env file if it was updated
        if ($this->envUpdated) {
            if (File::exists(base_path('.env.example'))) {
                File::copy(base_path('.env.example'), base_path('.env'));
                $this->info(".env file restored to default.");
            }
        }

        $this->info('Rollback completed successfully.');
    }
}