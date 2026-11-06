<?php

namespace App\Console\Commands;

use App\Services\Production\Milling\AutoAnalysisRequestService;
use Illuminate\Console\Command;

class AutoCreateAnalysisRequests extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'production:auto-analysis-requests';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically generate Input and Output Production Analysis Requests for running active plants based on min-max intervals.';

    /**
     * Execute the console command.
     */
    public function handle(AutoAnalysisRequestService $service)
    {
        $this->info('[' . now()->toDateTimeString() . '] Checking active running plants for auto analysis requests...');

        $result = $service->checkAndGenerateRequests();

        $this->info("Checked {$result['total_plants_checked']} plant(s). Generated {$result['created_count']} request(s).");

        if (!empty($result['created'])) {
            foreach ($result['created'] as $created) {
                $this->line(" - Created <info>{$created['request_no']}</info> for plant <comment>{$created['plant_name']}</comment> ({$created['type']})");
            }
        }

        if (!empty($result['skipped'])) {
            foreach ($result['skipped'] as $skipped) {
                $this->line(" - Skipped plant <comment>{$skipped['plant_name']}</comment>: {$skipped['reason']}");
            }
        }

        return Command::SUCCESS;
    }
}
