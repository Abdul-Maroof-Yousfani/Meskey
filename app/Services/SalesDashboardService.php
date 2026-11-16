<?php

namespace App\Services;

use App\Models\Sales\DeliveryChallan;
use App\Models\Sales\DeliveryOrder;
use App\Models\Sales\LoadingProgramItem;
use App\Models\Sales\SalesInvoice;
use App\Models\Sales\SalesOrder;
use App\Models\Sales\SalesQc;
use App\Models\Sales\SalesReturn;
use App\Models\Sales\SecondWeighbridge;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Sales dashboard.
 *
 * Every box is defined ONCE in definitions(): the same query gives the box
 * number (count / sum) and the list that opens on click, so both always match.
 *
 * Boxes in the "overview" group use the From/To date (created_at).
 * "Approval" and "truck stage" boxes show everything still pending, whatever its date.
 */
class SalesDashboardService
{
    /** Group => [title, icon] in display order. */
    public const GROUPS = [
        'overview' => ['Overview (Selected Dates)', 'ft-bar-chart-2'],
        'trucks'   => ['Trucks In Process (Pending Now)', 'ft-truck'],
        'approval' => ['Approvals Pending', 'ft-check-square'],
    ];

    private const CLOSED_SO = ['close-contract-due-to-market-down', 'close-with-market-rate-penalty', 'closed', 'close'];

    private Request $request;
    private int $companyId;
    private array $dateRange;
    private bool $isSuperAdmin;
    private array $arrivalLocations;

    public function boot(Request $request): self
    {
        $user = auth()->user();
        $from = $request->get('from_date', Carbon::today()->format('Y-m-d'));
        $to = $request->get('to_date', Carbon::today()->format('Y-m-d'));

        $this->request = $request;
        $this->companyId = (int) ($request->company_id ?: $user->current_company_id);
        $this->dateRange = [Carbon::parse($from)->startOfDay(), Carbon::parse($to)->endOfDay()];
        $this->isSuperAdmin = $user->user_type === 'super-admin';

        // Same arrival-location access the sales screens use
        $locations = getUserCurrentCompanyArrivalLocations();
        if (empty($locations) && $user->arrival_location_id) {
            $locations = [$user->arrival_location_id];
        }
        $this->arrivalLocations = $locations ?: [];

        return $this;
    }

    /** Dashboard module => groups it shows. */
    public const MODULES = [
        'sales'        => ['overview', 'approval'],
        'sales_trucks' => ['trucks'],
    ];

    /** All box values for the dashboard: ['type' => number]. Only the given groups are queried. */
    public function getData(Request $request, ?array $groups = null): array
    {
        $this->boot($request);

        $data = [];
        foreach ($this->definitions() as $type => $def) {
            if ($groups !== null && !in_array($def['group'], $groups, true)) {
                continue;
            }
            $query = $def['query']();
            $data[$type] = isset($def['sum'])
                ? round((float) $query->sum($def['sum']), 2)
                : $query->count();
        }

        return $data;
    }

    /** Box definitions grouped for the view. */
    public function boxes(?array $groups = null): array
    {
        $boxes = [];
        foreach ($this->definitions() as $type => $def) {
            if ($groups !== null && !in_array($def['group'], $groups, true)) {
                continue;
            }
            $boxes[$def['group']][$type] = $def;
        }

        return $boxes;
    }

    /** Records behind one box, ready for a generic table. */
    public function listData(string $type, Request $request): array
    {
        $this->boot($request);

        $def = $this->definitions()[$type] ?? null;
        abort_if(!$def, 404);

        $records = $def['query']()->with($def['with'] ?? [])->latest()->get();

        return [
            'title'   => $def['title'],
            'columns' => array_keys($def['row'](null)),
            'rows'    => $records->map(fn ($r) => array_values($def['row']($r)))->all(),
        ];
    }

    /* ------------------------------------------------------------------ */
    /*  Box definitions                                                    */
    /* ------------------------------------------------------------------ */

    private function definitions(): array
    {
        $soRow = fn ($so) => [
            'SO No'      => $so?->reference_no,
            'Customer'   => $so?->customer?->name,
            'Order Date' => $this->date($so?->order_date),
            'Status'     => $this->badge($so?->am_approval_status),
            'Created'    => $this->dateTime($so?->created_at),
        ];

        $doRow = fn ($do) => [
            'DO No'         => $do?->reference_no,
            'SO No'         => $do?->salesOrder?->reference_no,
            'Customer'      => $do?->customer?->name,
            'Dispatch Date' => $this->date($do?->dispatch_date),
            'Status'        => $this->badge($do?->am_approval_status),
            'Created'       => $this->dateTime($do?->created_at),
        ];

        $truckRow = fn ($item) => [
            'Ticket No'        => $item?->transaction_number,
            'Truck No'         => $item?->truck_number,
            'DO No'            => $item ? $this->truckDoNos($item) : null,
            'Customer'         => $item ? $this->truckCustomer($item) : null,
            'Arrival Location' => $item?->arrivalLocation?->name,
            'Process Status'   => $item?->process_status ?: 'In Process',
            'Created'          => $this->dateTime($item?->created_at),
        ];
        $truckWith = ['arrivalLocation', 'deliveryOrders.customer', 'loadingProgram.deliveryOrder.customer'];

        $dcRow = fn ($dc) => [
            'DC No'         => $dc?->dc_no,
            'Customer'      => $dc?->customer?->name,
            'Dispatch Date' => $this->date($dc?->dispatch_date),
            'Status'        => $this->badge($dc?->am_approval_status),
            'Created'       => $this->dateTime($dc?->created_at),
        ];

        $siRow = fn ($si) => [
            'Invoice No'   => $si?->si_no,
            'Customer'     => $si?->customer?->name,
            'Invoice Date' => $this->date($si?->invoice_date),
            'Status'       => $this->badge($si?->am_approval_status),
            'Created'      => $this->dateTime($si?->created_at),
        ];

        $swRow = fn ($sw) => [
            'Ticket No'      => $sw?->loadingSlip?->loadingProgramItem?->transaction_number,
            'Truck No'       => $sw?->loadingSlip?->loadingProgramItem?->truck_number,
            'Customer'       => $sw?->loadingSlip?->customer,
            'First Weight'   => $this->num($sw?->first_weight),
            'Second Weight'  => $this->num($sw?->second_weight),
            'Net Weight (Kg)' => $this->num($sw?->net_weight),
            'Weighed At'     => $this->dateTime($sw?->created_at),
        ];
        $swWith = ['loadingSlip.loadingProgramItem'];

        return [
            /* ---------------- Overview (date range) ---------------- */
            'sale_orders' => [
                'group' => 'overview', 'icon' => 'ft-file-text',
                'title' => 'Sale Orders', 'subtitle' => 'Created in selected dates',
                'query' => fn () => $this->saleOrders()->whereBetween('created_at', $this->dateRange),
                'with' => ['customer'], 'row' => $soRow,
            ],
            'delivery_orders' => [
                'group' => 'overview', 'icon' => 'ft-clipboard',
                'title' => 'Delivery Orders', 'subtitle' => 'Created in selected dates',
                'query' => fn () => $this->deliveryOrders()->whereBetween('created_at', $this->dateRange),
                'with' => ['customer', 'salesOrder'], 'row' => $doRow,
            ],
            'trucks_dispatched' => [
                'group' => 'overview', 'icon' => 'ft-truck',
                'title' => 'Trucks Dispatched', 'subtitle' => '2nd weighing done in selected dates',
                'query' => fn () => $this->secondWeighbridges()->whereBetween('sales_second_weighbridges.created_at', $this->dateRange),
                'with' => $swWith, 'row' => $swRow,
            ],
            'dispatched_kgs' => [
                'group' => 'overview', 'icon' => 'ft-package', 'suffix' => ' Kgs',
                'title' => 'Dispatched Weight', 'subtitle' => 'Net weight of dispatched trucks',
                'query' => fn () => $this->secondWeighbridges()->whereBetween('sales_second_weighbridges.created_at', $this->dateRange),
                'sum' => 'sales_second_weighbridges.net_weight',
                'with' => $swWith, 'row' => $swRow,
            ],
            'delivery_challans' => [
                'group' => 'overview', 'icon' => 'ft-file',
                'title' => 'Delivery Challans', 'subtitle' => 'Created in selected dates',
                'query' => fn () => $this->deliveryChallans()->whereBetween('delivery_challans.created_at', $this->dateRange),
                'with' => ['customer'], 'row' => $dcRow,
            ],
            'sales_invoices' => [
                'group' => 'overview', 'icon' => 'ft-file-plus',
                'title' => 'Sales Invoices', 'subtitle' => 'Created in selected dates',
                'query' => fn () => $this->salesInvoices()->whereBetween('created_at', $this->dateRange),
                'with' => ['customer'], 'row' => $siRow,
            ],

            /* ---------------- Truck stages (pending now) ---------------- */
            'loading_program_pending' => [
                'group' => 'trucks', 'icon' => 'ft-list',
                'title' => 'Loading Program Pending', 'subtitle' => 'Approved DOs without a loading program',
                'query' => fn () => $this->deliveryOrders()
                    ->where('am_approval_status', 'approved')
                    ->where(fn ($q) => $q->whereNull('do_status')->orWhere('do_status', '!=', 'closed'))
                    ->whereDoesntHaveClosedSaleOrder()
                    ->whereDoesntHave('loadingPrograms')
                    // A loading program can also carry several DOs through its pivot table
                    ->whereNotIn('delivery_order.id', DB::table('loading_program_delivery_order')->select('delivery_order_id'))
                    ->whereNotIn('delivery_order.id', DB::table('loading_program_item_delivery_order')->select('delivery_order_id')),
                'with' => ['customer', 'salesOrder'], 'row' => $doRow,
            ],
            'truck_first_weighbridge' => [
                'group' => 'trucks', 'icon' => 'ft-truck',
                'title' => 'Truck at 1st Weighbridge', 'subtitle' => 'First weighing pending',
                'query' => fn () => $this->openTrucks()->whereDoesntHave('firstWeighbridge'),
                'with' => $truckWith, 'row' => $truckRow,
            ],
            'truck_sales_qc' => [
                'group' => 'trucks', 'icon' => 'ft-layers',
                'title' => 'Truck at Sales QC', 'subtitle' => 'Weighed, QC pending',
                'query' => fn () => $this->openTrucks()->whereHas('firstWeighbridge')->whereDoesntHave('salesQc'),
                'with' => $truckWith, 'row' => $truckRow,
            ],
            'truck_loading' => [
                'group' => 'trucks', 'icon' => 'ft-box',
                'title' => 'Truck at Loading', 'subtitle' => 'QC accepted, loading slip pending',
                'query' => fn () => $this->openTrucks()
                    ->whereHas('salesQc', fn ($q) => $q->where('status', 'accept')->orWhere('am_approval_status', 'approved'))
                    ->whereDoesntHave('loadingSlip'),
                'with' => $truckWith, 'row' => $truckRow,
            ],
            'truck_dispatch_qc' => [
                'group' => 'trucks', 'icon' => 'ft-check-circle',
                'title' => 'Truck at Dispatch QC', 'subtitle' => 'Loaded, dispatch QC not accepted yet',
                'query' => fn () => $this->openTrucks()
                    ->whereHas('loadingSlip')
                    ->whereDoesntHave('dispatchQcs', fn ($q) => $q->where('status', 'accept')),
                'with' => $truckWith, 'row' => $truckRow,
            ],
            'truck_second_weighbridge' => [
                'group' => 'trucks', 'icon' => 'ft-truck',
                'title' => 'Truck at 2nd Weighbridge', 'subtitle' => 'Dispatch QC accepted, 2nd weighing pending',
                'query' => fn () => $this->openTrucks()
                    ->whereHas('loadingSlip')
                    ->whereHas('dispatchQcs', fn ($q) => $q->where('status', 'accept'))
                    ->whereDoesntHave('secondWeighbridge'),
                'with' => $truckWith, 'row' => $truckRow,
            ],
            'dc_pending' => [
                'group' => 'trucks', 'icon' => 'ft-file',
                'title' => 'Delivery Challan Pending', 'subtitle' => 'Weighed trucks without a DC',
                // A weighed truck still gets its DC after the DO is closed
                'query' => fn () => $this->trucks()
                    ->whereHas('loadingSlip.secondWeighbridge')
                    ->whereDoesntHave('delivery_challan_data'),
                'with' => $truckWith, 'row' => $truckRow,
            ],
            'invoice_pending' => [
                'group' => 'trucks', 'icon' => 'ft-file-plus',
                'title' => 'Sales Invoice Pending', 'subtitle' => 'Approved DCs not invoiced yet',
                'query' => fn () => $this->deliveryChallans()
                    ->where('delivery_challans.am_approval_status', 'approved')
                    ->whereHas('delivery_challan_data', fn ($q) => $q->whereNotIn(
                        'delivery_challan_data.id',
                        DB::table('sales_invoice_data')
                            ->join('sales_invoices', 'sales_invoices.id', '=', 'sales_invoice_data.sales_invoice_id')
                            ->where('sales_invoices.am_approval_status', '!=', 'rejected')
                            ->whereNotNull('sales_invoice_data.dc_data_id')
                            ->select('sales_invoice_data.dc_data_id')
                    )),
                'with' => ['customer'], 'row' => $dcRow,
            ],

            /* ---------------- Approvals pending ---------------- */
            'so_approval_pending' => [
                'group' => 'approval', 'icon' => 'ft-file-text', 'tone' => 'warning',
                'title' => 'Sale Order Approval', 'subtitle' => 'Pending approval',
                'query' => fn () => $this->saleOrders()->where('am_approval_status', 'pending'),
                'with' => ['customer'], 'row' => $soRow,
            ],
            'do_approval_pending' => [
                'group' => 'approval', 'icon' => 'ft-clipboard', 'tone' => 'warning',
                'title' => 'Delivery Order Approval', 'subtitle' => 'Pending approval',
                'query' => fn () => $this->deliveryOrders()->where('am_approval_status', 'pending'),
                'with' => ['customer', 'salesOrder'], 'row' => $doRow,
            ],
            'sales_qc_rejection_pending' => [
                'group' => 'approval', 'icon' => 'ft-x-circle', 'tone' => 'danger',
                'title' => 'Sales QC Rejection', 'subtitle' => 'Rejected by QC, approval pending',
                'query' => fn () => $this->trucks()->whereHas('salesQc', fn ($q) => $q
                    ->where('status', 'reject')->where('am_approval_status', 'pending')),
                'with' => $truckWith, 'row' => $truckRow,
            ],
            'dc_approval_pending' => [
                'group' => 'approval', 'icon' => 'ft-file', 'tone' => 'warning',
                'title' => 'Delivery Challan Approval', 'subtitle' => 'Pending approval',
                'query' => fn () => $this->deliveryChallans()->where('delivery_challans.am_approval_status', 'pending'),
                'with' => ['customer'], 'row' => $dcRow,
            ],
            'invoice_approval_pending' => [
                'group' => 'approval', 'icon' => 'ft-file-plus', 'tone' => 'warning',
                'title' => 'Sales Invoice Approval', 'subtitle' => 'Pending approval',
                'query' => fn () => $this->salesInvoices()->where('am_approval_status', 'pending'),
                'with' => ['customer'], 'row' => $siRow,
            ],
            'sales_return_approval_pending' => [
                'group' => 'approval', 'icon' => 'ft-corner-up-left', 'tone' => 'warning',
                'title' => 'Sales Return Approval', 'subtitle' => 'Pending approval',
                'query' => fn () => $this->salesReturns()->where('am_approval_status', 'pending'),
                'with' => ['customer'],
                'row' => fn ($sr) => [
                    'Return No' => $sr?->sr_no,
                    'Customer'  => $sr?->customer?->name,
                    'Date'      => $this->date($sr?->date),
                    'Status'    => $this->badge($sr?->am_approval_status),
                    'Created'   => $this->dateTime($sr?->created_at),
                ],
            ],
        ];
    }

    /* ------------------------------------------------------------------ */
    /*  Base queries (company + location + access filters)                 */
    /* ------------------------------------------------------------------ */

    private function locationId()
    {
        return $this->request->filled('location_id') ? $this->request->location_id : null;
    }

    private function saleOrders()
    {
        return SalesOrder::where('company_id', $this->companyId)
            ->when($this->locationId(), fn ($q, $loc) => $q->whereHas('locations', fn ($l) => $l->where('location_id', $loc)));
    }

    private function deliveryOrders()
    {
        return DeliveryOrder::where('delivery_order.company_id', $this->companyId)
            ->when($this->locationId(), fn ($q, $loc) => $q->where('delivery_order.location_id', $loc));
    }

    private function deliveryChallans()
    {
        return DeliveryChallan::where('delivery_challans.company_id', $this->companyId)
            ->when($this->locationId(), fn ($q, $loc) => $q->where('delivery_challans.location_id', $loc));
    }

    private function salesInvoices()
    {
        return SalesInvoice::where('company_id', $this->companyId)
            ->when($this->locationId(), fn ($q, $loc) => $q->where('location_id', $loc));
    }

    private function salesReturns()
    {
        return SalesReturn::where('company_id', $this->companyId)
            ->when($this->locationId(), fn ($q, $loc) => $q->where('company_location_id', $loc));
    }

    /** Sale trucks (loading program items) of this company the user can see. */
    private function trucks()
    {
        return LoadingProgramItem::whereHas('loadingProgram', function ($q) {
            $q->where('company_id', $this->companyId)
                ->when($this->locationId(), fn ($lq, $loc) => $lq->where('company_location_id', $loc));
        })
            ->when(!$this->isSuperAdmin, fn ($q) => $q->whereIn('arrival_location_id', $this->arrivalLocations));
    }

    /** Trucks still moving: SO not closed/cancelled and DO not closed (same rules as the sales screens). */
    private function openTrucks()
    {
        return $this->trucks()->whereDoesntHaveClosedSaleOrder()->WhereDeliveryStatusNotClosed();
    }

    private function secondWeighbridges()
    {
        return SecondWeighbridge::where('sales_second_weighbridges.company_id', $this->companyId)
            ->whereHas('loadingSlip.loadingProgramItem', function ($q) {
                $q->when(!$this->isSuperAdmin, fn ($iq) => $iq->whereIn('arrival_location_id', $this->arrivalLocations))
                    ->when($this->locationId(), fn ($iq, $loc) => $iq->whereHas('loadingProgram', fn ($lq) => $lq->where('company_location_id', $loc)));
            });
    }

    /* ------------------------------------------------------------------ */
    /*  Formatting helpers for the list                                    */
    /* ------------------------------------------------------------------ */

    private function truckDoNos($item): ?string
    {
        $nos = $item->deliveryOrders->pluck('reference_no')->filter();
        if ($nos->isEmpty() && $item->loadingProgram?->deliveryOrder) {
            $nos = collect([$item->loadingProgram->deliveryOrder->reference_no]);
        }

        return $nos->unique()->implode(', ') ?: null;
    }

    private function truckCustomer($item): ?string
    {
        $do = $item->deliveryOrders->first() ?: $item->loadingProgram?->deliveryOrder;

        return $do?->customer?->name;
    }

    private function date($value): ?string
    {
        return $value ? Carbon::parse($value)->format('d-m-Y') : null;
    }

    private function dateTime($value): ?string
    {
        return $value ? Carbon::parse($value)->format('d-m-Y h:i A') : null;
    }

    private function num($value): ?string
    {
        return $value === null ? null : number_format((float) $value, 2);
    }

    /** Status as a badge (HTML, rendered unescaped in the list). */
    private function badge($status): array
    {
        $status = strtolower((string) $status);
        $class = match ($status) {
            'approved' => 'badge-success',
            'rejected' => 'badge-danger',
            'pending' => 'badge-warning',
            'reverted', 'returned' => 'badge-info',
            default => 'badge-secondary',
        };

        return ['badge' => $status ? ucfirst($status) : '-', 'class' => $class];
    }
}
