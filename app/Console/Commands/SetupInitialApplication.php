<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class SetupInitialApplication extends Command
{
    protected $signature = 'app:setup';
    protected $description = 'Setup the application by creating required folders, deleting public/storage, and running migrations and seeds.';

    public function handle()
    {
        // Step 1: Check and create required folders
        $folders = [
            storage_path('framework/cache'),
            storage_path('framework/sessions'),
            storage_path('framework/views'),
        ];

        foreach ($folders as $folder) {
            if (!File::exists($folder)) {
                File::makeDirectory($folder, 0755, true);
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

        // Step 3: First confirmation
        $confirmation = $this->confirm('Are you sure you want to proceed? This will truncate all your initial data & Storages. Type "yes" to confirm ');

        if ($confirmation) {
            // Step 4: Generate a random number for second confirmation
            $randomNumber = rand(100000, 999999); // Generate a random 4-digit number
            $userInput = $this->ask("Are you sure you want to continue? To proceed, please type the following number: $randomNumber");
            if ($userInput == $randomNumber) {
                // Step 5: Run migrations and seeds
                $this->call('migrate:fresh');
                $this->call('db:seed');
                $this->call('storage:link');

                $this->info('Application setup completed successfully.');
            } else {
                $this->error('Incorrect number. Setup aborted.');
            }
        } else {
            $this->info('Setup aborted.');
        }
    }
}