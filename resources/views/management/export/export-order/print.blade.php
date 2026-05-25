@if(strcasecmp($exportOrder->packing_type, 'In Container') == 0 || strcasecmp($exportOrder->packing_type, 'In Conatiner') == 0)
    @include('management.export.export-order.prints.container')
@elseif(strcasecmp($exportOrder->packing_type, 'In Bulk') == 0 && strcasecmp($exportOrder->incoterm->name ?? '', 'FOB') == 0)
    @include('management.export.export-order.prints.bulk_fob')
@elseif(strcasecmp($exportOrder->packing_type, 'In Bulk') == 0 && (strcasecmp($exportOrder->incoterm->name ?? '', 'CNF') == 0 || strcasecmp($exportOrder->incoterm->name ?? '', 'CIF') == 0))
    @include('management.export.export-order.prints.bulk_cnf')
@else
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Template Not Defined</title>
        <style>
            body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
            .message { border: 1px solid #ccc; padding: 30px; display: inline-block; background: #f9f9f9; }
            h2 { color: #d9534f; }
        </style>
    </head>
    <body>
        <div class="message">
            <h2>Template Not Defined</h2>
            <p>The print template for Packing Type <strong>{{ $exportOrder->packing_type }}</strong> and Incoterm <strong>{{ $exportOrder->incoterm->name ?? 'N/A' }}</strong> is not yet defined.</p>
            <button onclick="window.history.back()" style="margin-top: 20px; padding: 10px 20px; cursor: pointer;">Go Back</button>
        </div>
    </body>
    </html>
@endif
