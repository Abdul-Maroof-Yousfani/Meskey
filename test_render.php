<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $controller = new App\Http\Controllers\Export\ExportDeliveryChallanController();
    $ticket = App\Models\Sales\LoadingProgramItem::has('exportLoadingProgram')->latest()->first();
    $request = new Illuminate\Http\Request(['ticket_id' => $ticket->id]); // Adjust ticket ID if needed
    echo $controller->getItemsByTickets($request)->render();
} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine() . "\n";
}
