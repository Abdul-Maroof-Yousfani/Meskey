@php
    $bagWeight = $arrivalPurchaseOrder->product->bag_weight_for_purchasing ?: 1;
@endphp
{{-- tkf-page tkf-2col tkf-view: mobile par form card jaisa, 2 columns (mobile-ui.css section 7). Desktop same. --}}
<div class="view-only tkf-page tkf-2col tkf-view">
    <div class="d-flex justify-content-end mb-2">
        {{-- Print: isi view ka data table (Field | Value) me print (poPrintView, neeche script) --}}
        <button type="button" class="btn btn-sm btn-outline-primary mr-2" onclick="poPrintView(this)">
            <i class="ft-printer mr-1"></i> Print
        </button>
        <button type="button" class="btn btn-sm btn-outline-info" onclick="openModal(this,'{{ route('raw-material.purchase-order.audit-logs', $arrivalPurchaseOrder->id) }}','Audit Logs: Contract #{{ $arrivalPurchaseOrder->contract_no }}', false, '55%')">
            <i class="fa fa-history mr-1"></i> Audit Logs
        </button>
    </div>
    <div class="row form-mar">
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group">
                <label>Location:</label>
                <select name="company_location_id_for_display" id="company_location_id" class="form-control" disabled>
                    <option value="{{ $arrivalPurchaseOrder->company_location_id }}" selected>
                        {{ $arrivalPurchaseOrder->location->name ?? 'N/A' }}
                    </option>
                </select>
            </div>
        </div>
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group">
                <label>Contract Date:</label>
                <input type="date" name="contract_date"
                    value="{{ isset($arrivalPurchaseOrder->contract_date) ? $arrivalPurchaseOrder->contract_date->format('Y-m-d') : null }}"
                    class="form-control" readonly />
            </div>
        </div>

        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group">
                <label>Contract No:</label>
                <input type="text" readonly name="contract_no" value="{{ $arrivalPurchaseOrder->contract_no }}"
                    class="form-control" />
            </div>
        </div>
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group">
                <label>Sauda Type:</label>
                <select name="sauda_type_id" class="form-control" disabled>
                    <option value="{{ $arrivalPurchaseOrder->saudaType->id ?? null }}">
                        {{ $arrivalPurchaseOrder->saudaType?->name ?? 'Sauda Type Name' }}
                    </option>
                </select>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <h6 class="header-heading-sepration">
                Supplier
            </h6>
        </div>
        <div class="col-xs-8 col-sm-8 col-md-8">
            <div class="form-group">
                <label>Supplier:</label>
                <select name="supplier_id" id="supplier_id" class="form-control" disabled>
                    <option value="{{ $arrivalPurchaseOrder->supplier->id ?? null }}" selected>
                        {{ $arrivalPurchaseOrder->supplier->name ?? 'Supplier' }}
                    </option>
                </select>
            </div>
        </div>
        <div class="col-xs-4 col-sm-4 col-md-4">
            <div class="form-group">
                <label>Commission:</label>
                <input type="number" name="supplier_commission" placeholder="Commission (per KG)"
                    value="{{ $arrivalPurchaseOrder->supplier_commission }}" class="form-control" step="any" readonly />
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-12">
            <h6 class="header-heading-sepration">
                Broker 1
            </h6>
        </div>
        <div class="col-xs-4 col-sm-4 col-md-4">
            <div class="form-group ">
                <label>Broker:</label>
                <select name="broker_one_id" id="broker_one_id" class="form-control" disabled>
                    <option value="">{{ $arrivalPurchaseOrder->broker->name ?? 'N/A' }}</option>
                </select>
            </div>
        </div>
        <div class="col-xs-4 col-sm-4 col-md-4">
            <div class="form-group">
                <label>Type:</label>
                <select class="form-control" disabled>
                    <option value="">
                        {{ $arrivalPurchaseOrder->broker_one_calculation_type ? ucfirst($arrivalPurchaseOrder->broker_one_calculation_type) . ' Wise' : 'N/A' }}
                    </option>
                </select>
            </div>
        </div>

        <div class="col-xs-4 col-sm-4 col-md-4">
            <div class="form-group ">
                <label>Commission:</label>
                <input type="number" value="{{ $arrivalPurchaseOrder->broker_one_commission }}"
                    id="broker_one_commission" name="broker_one_commission" placeholder="Commission"
                    class="form-control" step="any" readonly />
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-12">
            <h6 class="header-heading-sepration">
                Broker 2
            </h6>
        </div>
        <div class="col-xs-4 col-sm-4 col-md-4">
            <div class="form-group ">
                <label>Broker:</label>
                <select name="broker_two_id" id="broker_two_id" class="form-control" disabled>
                    <option value="">{{ $arrivalPurchaseOrder->brokerTwo->name ?? 'N/A' }}</option>
                </select>
            </div>
        </div>
        <div class="col-xs-4 col-sm-4 col-md-4">
            <div class="form-group">
                <label>Type:</label>
                <select class="form-control" disabled>
                    <option value="">
                        {{ $arrivalPurchaseOrder->broker_two_calculation_type ? ucfirst($arrivalPurchaseOrder->broker_two_calculation_type) . ' Wise' : 'N/A' }}
                    </option>
                </select>
            </div>
        </div>
        <div class="col-xs-4 col-sm-4 col-md-4">
            <div class="form-group ">
                <label>Commission:</label>
                <input type="number" value="{{ $arrivalPurchaseOrder->broker_two_commission }}"
                    id="broker_two_commission" name="broker_two_commission" placeholder="Commission"
                    class="form-control" step="any" readonly />

            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-12">
            <h6 class="header-heading-sepration">
                Broker 3
            </h6>
        </div>
        <div class="col-xs-4 col-sm-4 col-md-4">
            <div class="form-group ">
                <label>Broker:</label>
                <select name="broker_three_id" id="broker_three_id" class="form-control" disabled>
                    <option value="">{{ $arrivalPurchaseOrder->brokerThree->name ?? 'N/A' }}</option>
                </select>
            </div>
        </div>
        <div class="col-xs-4 col-sm-4 col-md-4">
            <div class="form-group">
                <label>Type:</label>
                <select class="form-control" disabled>
                    <option value="">
                        {{ $arrivalPurchaseOrder->broker_three_calculation_type ? ucfirst($arrivalPurchaseOrder->broker_three_calculation_type) . ' Wise' : 'N/A' }}
                    </option>
                </select>
            </div>
        </div>
        <div class="col-xs-4 col-sm-4 col-md-4">
            <div class="form-group ">
                <label>Commission:</label>
                <input type="number" value="{{ $arrivalPurchaseOrder->broker_three_commission }}"
                    id="broker_three_commission" name="broker_three_commission" placeholder="Commission"
                    class="form-control" step="any" readonly />

            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Commodity:</label>
                <select name="product_id" id="product_id" class="form-control" disabled>
                    <option value="">{{ $arrivalPurchaseOrder->product->name ?? 'N/A' }}</option>
                </select>
            </div>
        </div>
        <div id="slabsContainer" class="col-xs-12 col-sm-12 col-md-12">
            {!! $slabsHtml !!}
        </div>
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Line:</label>
                <select name="line_type" id="line_type" class="form-control" disabled>
                    <option value="">{{ ucfirst($arrivalPurchaseOrder->line_type) }}</option>
                </select>
            </div>
        </div>
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group">
                <label>Bags Weight (kg):</label>
                <input type="number" name="bag_weight" value="{{ $arrivalPurchaseOrder->bag_weight }}"
                    placeholder="Bags Weight (kg)" class="form-control" readonly />
            </div>
        </div>
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group">
                <label>Bags Rate:</label>
                <input type="number" name="bag_rate" value="{{ $arrivalPurchaseOrder->bag_rate }}"
                    placeholder="Bags Rate" class="form-control" readonly />
            </div>
        </div>
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group">
                <label>Delivery Date:</label>
                <input type="date" name="delivery_date"
                    value="{{ isset($arrivalPurchaseOrder->delivery_date) ? $arrivalPurchaseOrder->delivery_date->format('Y-m-d') : null }}"
                    placeholder="Delivery Date" class="form-control" readonly />
            </div>
        </div>
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group">
                <label>Credit Days:</label>
                <input type="number" name="credit_days" value="{{ $arrivalPurchaseOrder->credit_days }}"
                    placeholder="Credit Days" class="form-control" readonly />
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <h6 class="header-heading-sepration">
                Rate
            </h6>
        </div>

        <div class="col-xs-4 col-sm-4 col-md-4">
            <div class="form-group">
                <label>Rate Per KG:</label>
                <input type="number" step="0.01" name="rate_per_kg" value="{{ $arrivalPurchaseOrder->rate_per_kg }}"
                    placeholder="Rate Per KG" class="form-control" readonly />
            </div>
        </div>
        <div class="col-xs-4 col-sm-4 col-md-4">
            <div class="form-group">
                <label>Rate Per Mound:</label>
                <input type="number" step="0.01" name="rate_per_mound"
                    value="{{ $arrivalPurchaseOrder->rate_per_mound }}" placeholder="Rate Per Mound"
                    class="form-control" readonly />
            </div>
        </div>
        <div class="col-xs-4 col-sm-4 col-md-4">
            <div class="form-group">
                <label>Rate Per 100KG:</label>
                <input type="number" step="0.01" name="rate_per_100kg"
                    value="{{ $arrivalPurchaseOrder->rate_per_100kg }}" placeholder="Rate Per 100KG"
                    class="form-control" readonly />
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <h6 class="header-heading-sepration">
                Quantity Calculation
            </h6>
        </div>

        <div class="col-xs-4 col-sm-4 col-md-4">
            <div class="form-group">
                <label>Type:</label>
                <select name="calculation_type" id="calculation_type" class="form-control" disabled>
                    <option value="trucks">
                        {{ $arrivalPurchaseOrder->calculation_type == 'trucks' ? 'Trucks Wise' : 'Quantity Wise' }}
                    </option>
                </select>
            </div>
        </div>
        <div class="col-xs-4 col-sm-4 col-md-4 fields-hidable"
            style="{{ $arrivalPurchaseOrder->calculation_type == 'quantity' ? 'display:none;' : '' }}">
            <div class="form-group">
                <label for="truck_size_range">Truck Size Ranges:</label>
                <select name="truck_size_range" id="truck_size_range" class="form-control" disabled>
                    <option value="">{{ $arrivalPurchaseOrder->truckSizeRange->name ?? 'N/A' }}</option>
                </select>
            </div>
        </div>

        <div class="col-xs-4 col-sm-4 col-md-4 fields-hidable"
            style="{{ $arrivalPurchaseOrder->calculation_type == 'quantity' ? 'display:none;' : '' }}">
            <div class="form-group">
                <label>No of Trucks:</label>
                <input type="number" name="no_of_trucks" id="no_of_trucks"
                    value="{{ $arrivalPurchaseOrder->no_of_trucks }}" placeholder="Number of Trucks"
                    class="form-control" readonly />
            </div>
        </div>
        <!-- <div class="col-xs-4 col-sm-4 col-md-4" id="quantity-field"
            style="{{ $arrivalPurchaseOrder->calculation_type == 'trucks' ? 'display:none;' : '' }}">
            <div class="form-group">
                <label>Total Quantity (kg):</label>
                <input type="number" name="total_quantity" id="total_quantity"
                    value="{{ $arrivalPurchaseOrder->total_quantity }}" placeholder="Total Quantity"
                    class="form-control" readonly />
            </div>
        </div> -->
        <div class="col-xs-4 col-sm-4 col-md-4" id="quantity-field"
            style="{{ $arrivalPurchaseOrder->calculation_type == 'trucks' ? 'display:none;' : '' }}">
            <div class="form-group">
                <label>Min Quantity (kg):</label>
                <input type="number" name="min_quantity" id="min_quantity"
                    value="{{ $arrivalPurchaseOrder->min_quantity }}" placeholder="Min Quantity" class="form-control"
                    readonly />
            </div>
        </div>
        <div class="col-xs-4 col-sm-4 col-md-4" id="quantity-field"
            style="{{ $arrivalPurchaseOrder->calculation_type == 'trucks' ? 'display:none;' : '' }}">
            <div class="form-group">
                <label>Max Quantity (kg):</label>
                <input type="number" name="max_quantity" id="max_quantity"
                    value="{{ $arrivalPurchaseOrder->max_quantity }}" placeholder="Max Quantity" class="form-control"
                    readonly />
            </div>
        </div>


        <div class="col-xs-4 col-sm-4 col-md-4">
            <div class="form-group">
                <label>Quantity Range:</label>
                <input type="text" name="quantity_range" id="quantity_range"
                    value="{{ $arrivalPurchaseOrder->min_quantity }} - {{ $arrivalPurchaseOrder->max_quantity }} kg"
                    placeholder="Quantity Range" class="form-control" readonly />
            </div>
        </div>

        <div class="col-xs-4 col-sm-4 col-md-4">
            <div class="form-group">
                <label>No of Bags Range:</label>
                <input type="text" name="bags_range" id="bags_range"
                    value="{{ $arrivalPurchaseOrder->min_quantity / $bagWeight }} - {{ $arrivalPurchaseOrder->max_quantity / $bagWeight }} bags"
                    placeholder="Bags Range" class="form-control" readonly />
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <h6 class="header-heading-sepration">
                Others
            </h6>
        </div>
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label class="label-control font-weight-bold">Contract Condition:</label>
                <div class="custom-control custom-radio">
                    <input type="radio" name="is_replacement" value="1" class="custom-control-input"
                        id="replacement-yes" {{ $arrivalPurchaseOrder->is_replacement == 1 ? 'checked' : '' }} disabled>
                    <label class="custom-control-label" for="replacement-yes">Replacement</label>
                </div>
                <div class="custom-control custom-radio">
                    <input type="radio" name="is_replacement" value="0" class="custom-control-input" id="replacement-no"
                        {{ $arrivalPurchaseOrder->is_replacement == 0 ? 'checked' : '' }} disabled>
                    <label class="custom-control-label" for="replacement-no">No Replacement</label>
                </div>
            </div>
        </div>
        <div class="col-xs-4 col-sm-4 col-md-4">
            <div class="form-group">
                <label>Weighbridge only From:</label>
                <input type="text" name="weighbridge_from" value="{{ $arrivalPurchaseOrder->weighbridge_from }}"
                    placeholder="Weighbridge From" class="form-control" readonly />
            </div>
        </div>
        <div class="col-xs-4 col-sm-4 col-md-4">
            <div class="form-group">
                <label>Delivery Address:</label>
                <input type="text" name="delivery_address" value="{{ $arrivalPurchaseOrder->delivery_address }}"
                    placeholder="Delivery Address" class="form-control" readonly />
            </div>
        </div>
        <div class="col-xs-4 col-sm-4 col-md-4">
            <div class="form-group ">
                <label>Division:</label>
                <select name="division_id" id="division_id" class="form-control" disabled>
                    <option value="{{ $arrivalPurchaseOrder->division->id ?? '' }}" selected>
                        {{ $arrivalPurchaseOrder->division->name ?? 'Select Division' }}
                    </option>
                </select>
            </div>
        </div>
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group ">
                <label>Contract Status:</label>
                <select name="contract_status" id="contract_status" class="form-control" disabled>
                    <option value="">{{ ucfirst(str_replace('-', ' ', $arrivalPurchaseOrder->contract_status)) }}
                    </option>
                </select>
            </div>
        </div>
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Remarks (Optional):</label>
                <textarea name="remarks" placeholder="Remarks" class="form-control"
                    readonly>{{ $arrivalPurchaseOrder->remarks }}</textarea>
            </div>
        </div>
    </div>

    <div class="row bottom-button-bar tkf-actions">
        <div class="col-12">
            <a type="button" class="btn btn-danger modal-sidebar-close closebutton">Close</a>
        </div>
    </div>
</div>
<x-approval-status-special :model="$arrivalPurchaseOrder" :list-refresh="route('raw-material.get.purchase-order')" />

<script>
    // ===== Print: view me jo dikh raha hai (har section + field) wahi table me =====
    // window par (var / function) taake popup dobara khulne par redeclare error na aaye
    window.poPrintView = function (btn) {
        var root = btn.closest('.view-only');
        if (!root) return;

        var esc = function (t) {
            return String(t == null ? '' : t).replace(/[&<>"]/g, function (c) {
                return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c];
            });
        };
        var clean = function (t) { return (t || '').replace(/\s+/g, ' ').trim(); };

        // ek form-group ki value (select = chuna hua naam, radio = chuna hua option, "from to to" jaisa input-group)
        var fieldValue = function (group) {
            var radios = group.querySelectorAll('input[type="radio"]');
            if (radios.length) {
                var checked = group.querySelector('input[type="radio"]:checked');
                var lbl = checked ? group.querySelector('label[for="' + checked.id + '"]') : null;
                return lbl ? clean(lbl.textContent) : '';
            }
            var parts = [];
            group.querySelectorAll('input, select, textarea, .input-group-text').forEach(function (el) {
                if (el.matches('input[type="hidden"], input[type="checkbox"]')) return;
                if (el.classList.contains('input-group-text')) { parts.push(clean(el.textContent)); return; }
                if (el.tagName === 'SELECT') {
                    var opt = el.options[el.selectedIndex];
                    parts.push(opt && opt.value !== '' ? clean(opt.text) : '');
                    return;
                }
                parts.push(clean(el.value));
            });
            return clean(parts.join(' '));
        };

        // sections: heading (h6) ke neeche wale fields
        var sections = [];
        var current = { title: 'Contract', rows: [] };
        sections.push(current);
        root.querySelectorAll('h6.header-heading-sepration, .form-group').forEach(function (el) {
            if (el.closest('.bottom-button-bar')) return;
            if (el.matches('h6')) {
                current = { title: clean(el.textContent).replace(/:$/, ''), rows: [] };
                sections.push(current);
                return;
            }
            var label = el.querySelector('label:not(.custom-control-label)');
            if (!label) return;
            var name = clean(label.textContent).replace(/:$/, '').replace(/\s*\(Optional\)$/i, '');
            var value = fieldValue(el);
            current.rows.push([name, value === '' ? '-' : value]);
        });

        // har section: 2 field ek line (Field | Value | Field | Value)
        var body = '';
        sections.forEach(function (sec) {
            if (!sec.rows.length) return;
            body += '<tr class="sec"><th colspan="4">' + esc(sec.title) + '</th></tr>';
            for (var i = 0; i < sec.rows.length; i += 2) {
                var a = sec.rows[i], b = sec.rows[i + 1];
                var wide = !b && (a[1].length > 40);
                body += '<tr><td class="k">' + esc(a[0]) + '</td><td' + (wide ? ' colspan="3"' : '') + '>' + esc(a[1]) + '</td>' +
                    (b ? '<td class="k">' + esc(b[0]) + '</td><td>' + esc(b[1]) + '</td>' : (wide ? '' : '<td class="k"></td><td></td>')) + '</tr>';
            }
        });

        var html = '<!doctype html><html><head><meta charset="utf-8"><title>Purchase Order - Contract #{{ $arrivalPurchaseOrder->contract_no }}</title>' +
            '<style>' +
            '@page{size:A4;margin:12mm}' +
            'body{font-family:Arial,Helvetica,sans-serif;font-size:12px;color:#222;margin:0}' +
            '.head{display:flex;justify-content:space-between;align-items:flex-end;border-bottom:2px solid #27489a;padding-bottom:8px;margin-bottom:12px}' +
            '.head h1{font-size:18px;margin:0;color:#27489a}.head .meta{text-align:right;font-size:11px;color:#555}' +
            'table{width:100%;border-collapse:collapse;table-layout:fixed}' +
            'td,th{border:1px solid #c9ced8;padding:6px 8px;vertical-align:top;word-wrap:break-word}' +
            'td.k{width:18%;background:#f4f6fa;font-weight:bold;color:#444}' +
            'tr.sec th{background:#27489a;color:#fff;text-align:left;font-size:12.5px;padding:7px 8px}' +
            'tr{page-break-inside:avoid}' +
            '*{-webkit-print-color-adjust:exact;print-color-adjust:exact}' +
            '</style></head><body>' +
            '<div class="head"><div><h1>Purchase Order</h1><div>Contract # <b>{{ $arrivalPurchaseOrder->contract_no }}</b></div></div>' +
            '<div class="meta">{{ getCurrentCompany()->name ?? '' }}<br>Printed: ' + esc(new Date().toLocaleString()) + '</div></div>' +
            '<table>' + body + '</table></body></html>';

        // chhupa iframe: popup blocker ka masla nahi
        var frame = document.createElement('iframe');
        frame.style.cssText = 'position:fixed;right:0;bottom:0;width:0;height:0;border:0;';
        document.body.appendChild(frame);
        var doc = frame.contentWindow.document;
        doc.open();
        doc.write(html);
        doc.close();
        setTimeout(function () {
            frame.contentWindow.focus();
            frame.contentWindow.print();
            setTimeout(function () { frame.remove(); }, 1000);
        }, 250);
    };

    $(document).ready(function () {
        $('.view-only .form-control').attr('disabled', true);
        $('.view-only .custom-control-input').attr('disabled', true);


        // Quantity and bags calculation
        function calculateQuantityAndBags() {
            const MIN_QTY = 0;
            const bagWeight = $('#product_id option:selected').data('bag-weight') || 1;
            let minQuantity, maxQuantity;

            if ($('#calculation_type').val() === 'trucks') {
                const trucks = parseInt($('#no_of_trucks').val()) || 0;
                minQuantity = trucks * TRUCK_MIN;
                maxQuantity = trucks * TRUCK_MAX;

                if (minQuantity < MIN_QTY) {
                    minQuantity = MIN_QTY;
                    maxQuantity = Math.max(MIN_QTY, maxQuantity);
                    $('#no_of_trucks').val(Math.ceil(MIN_QTY / TRUCK_MIN));
                }
            } else {
                minQuantity = parseInt($('#min_quantity_input').val()) || MIN_QTY;
                maxQuantity = parseInt($('#max_quantity_input').val()) || minQuantity;

                if (minQuantity < MIN_QTY) {
                    minQuantity = MIN_QTY;
                    $('#min_quantity_input').val(MIN_QTY);
                }
            }

            const minBags = Math.ceil(minQuantity / bagWeight);
            const maxBags = Math.ceil(maxQuantity / bagWeight);

            $('#quantity_range').val(minQuantity === maxQuantity ?
                minQuantity.toLocaleString() + ' kg' :
                minQuantity.toLocaleString() + ' - ' + maxQuantity.toLocaleString() + ' kg');

            $('#bags_range').val(minBags === maxBags ?
                minBags.toLocaleString() + ' bags' :
                minBags.toLocaleString() + ' - ' + maxBags.toLocaleString() + ' bags');

            $('#minQty').val(minQuantity);
            $('#maxQty').val(maxQuantity);
            $('#minBags').val(minBags);
            $('#maxBags').val(maxBags);
        }

    });
</script>