<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$rows = \App\Models\Export\ExportDeliveryChallanData::with('loadingProgramItem.exportLoadingProgram')->latest()->take(5)->get();
foreach ($rows as $row) {
    echo "DC Data ID: {$row->id}, Ticket ID: {$row->ticket_id}, Vessel: " . ($row->loadingProgramItem?->exportLoadingProgram?->vessel_name ?? 'NULL') . "\n";
}
