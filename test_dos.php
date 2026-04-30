<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $controller = new App\Http\Controllers\Export\ExportDeliveryChallanController();
    $ticket = App\Models\Sales\LoadingProgramItem::has('exportLoadingProgram')->latest()->first();
    if($ticket) {
        $dos = $controller->resolveDeliveryOrders($ticket);
        echo "DOs found: " . $dos->count() . "\n";
        
        $deliveryOrders = collect();
        $ticketDOs = $ticket->deliveryOrders()->withoutGlobalScopes()->get();
        if ($ticketDOs->isNotEmpty()) {
            $deliveryOrders = $deliveryOrders->merge($ticketDOs);
        }
        if ($ticket->exportLoadingProgram) {
            $lpDOs = $ticket->exportLoadingProgram->deliveryOrders()->withoutGlobalScopes()->get();
            if ($lpDOs->isNotEmpty()) {
                $deliveryOrders = $deliveryOrders->merge($lpDOs);
            }
            if ($ticket->exportLoadingProgram->deliveryOrder) {
                $deliveryOrders->push($ticket->exportLoadingProgram->deliveryOrder);
            }
        }
        $deliveryOrders = $deliveryOrders->filter()->where('type', 'export_order')->unique('id')->values();
        
        echo "Blade DOs found: " . $deliveryOrders->count() . "\n";
        
        foreach ($deliveryOrders as $delivery_order) {
            echo "DO ID: " . $delivery_order->id . " has exportPackingItems: " . $delivery_order->exportPackingItems()->count() . "\n";
        }
    } else {
        echo "No ticket found\n";
    }
} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n" . $e->getTraceAsString();
}
