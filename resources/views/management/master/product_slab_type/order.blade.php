@extends('management.layouts.master')
@section('title')
    Product Slab Type Order By
@endsection

@section('content')
    <div class="content-wrapper">
        <section id="product-slab-type-order-section">
            <div class="row w-100 mx-auto align-items-center mb-2">
                <div class="col-xs-12 col-sm-6 col-md-6 col-lg-6">
                    <h2 class="page-title mb-0">Product Slab Type Order By</h2>
                </div>
                <div class="col-xs-12 col-sm-6 col-md-6 col-lg-6 text-right mt-2 mt-sm-0">
                    <a href="{{ route('product-slab-type.index') }}" onclick="loadPageContent('{{ route('product-slab-type.index') }}')" class="btn btn-secondary mr-2">
                        <i class="ft-arrow-left mr-1"></i> Back to Product Slab Types
                    </a>
                    <button id="btn-save-order" type="button" class="btn btn-primary">
                        <i class="fa fa-save mr-1"></i> <span id="save-btn-text">Save Order</span>
                    </button>
                </div>
            </div>
            
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h4 class="card-title mb-0">
                                <p class="mb-2"><small>Drag and drop the rows to reorder Product Slab Types for General Items</small></p>
                                {{-- <i class="ft-move mr-2 text-primary"></i> General Item Slab Types ({{ count($slab_types) }}) --}}
                            </h4>
                            <span id="save-status" class="badge badge-light-success d-none">
                                <i class="fa fa-check mr-1"></i> Order Saved
                            </span>
                        </div>
                        <div class="card-content">
                            <div class="card-body table-responsive p-0">
                                <table class="table table-hover m-0" id="sortable-table">
                                    <thead class="bg-light">
                                        <tr>
                                            <th style="width: 70px;" class="text-center">Move</th>
                                            <th style="width: 100px;" class="text-center">Order By</th>
                                            <th>Name</th>
                                            <th>QC Symbol</th>
                                            <th>Calculation Base Type</th>
                                            <th>Description</th>
                                            <th style="width: 120px;" class="text-center">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody id="sortable-tbody">
                                        @forelse ($slab_types as $key => $slab)
                                            <tr class="sortable-row cursor-move" data-id="{{ $slab->id }}">
                                                <td class="text-center handle align-middle">
                                                    <div class="drag-icon-box" title="Drag to reorder">
                                                        <i class="ft-menu font-medium-3 text-muted"></i>
                                                    </div>
                                                </td>
                                                <td class="text-center align-middle">
                                                    <span class="badge badge-primary font-medium-1 order-number px-2 py-1">
                                                        {{ $slab->order_by ?? ($key + 1) }}
                                                    </span>
                                                    <input type="hidden" class="slab-order-val" value="{{ $slab->order_by ?? ($key + 1) }}">
                                                </td>
                                                <td class="align-middle font-weight-bold">
                                                    {{ $slab->name }}
                                                </td>
                                                <td class="align-middle">
                                                    @if($slab->qc_symbol)
                                                        <span class="badge badge-secondary">{{ $slab->qc_symbol }}</span>
                                                    @else
                                                        <span class="text-muted">--</span>
                                                    @endif
                                                </td>
                                                <td class="align-middle">
                                                    {{ $slab->calculation_base_type ?? '--' }}
                                                </td>
                                                <td class="align-middle">
                                                    <small class="text-muted">{{ $slab->description ?? '--' }}</small>
                                                </td>
                                                <td class="text-center align-middle">
                                                    <span class="badge bg-light-{{ $slab->status == 'inactive' ? 'danger' : 'success' }}">
                                                        {{ ucfirst($slab->status) }}
                                                    </span>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="7" class="text-center py-4 text-muted">
                                                    No Slab Types found with "Slab type for general item" enabled.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection

@section('style')
    <style>
        #sortable-table .sortable-row,
        #sortable-table .sortable-row td {
            cursor: move;
            user-select: none;
            transition: background-color 0.2s ease;
        }

        #sortable-table .sortable-row:hover {
            background-color: #f7fafc;
        }

        #sortable-table .handle,
        .drag-icon-box {
            cursor: move;
        }

        .drag-icon-box {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 34px;
            height: 34px;
            border-radius: 6px;
            background: #f1f3f5;
            transition: all 0.2s;
        }

        #sortable-table .sortable-row:hover .drag-icon-box {
            background: #e2e8f0;
            color: #3182ce !important;
        }

        .sortable-ghost {
            opacity: 0.35;
            background-color: #ebf8ff !important;
            border: 2px dashed #3182ce !important;
        }

        .sortable-chosen {
            background-color: #edf2f7 !important;
        }

        .order-number {
            min-width: 36px;
            display: inline-block;
        }
    </style>
@endsection

@section('script')
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.14.0/Sortable.min.js"></script>
    <script>
        $(document).ready(function() {
            var sortableContainer = document.getElementById('sortable-tbody');

            if (sortableContainer) {
                new Sortable(sortableContainer, {
                    animation: 200,
                    ghostClass: 'sortable-ghost',
                    chosenClass: 'sortable-chosen',
                    onEnd: function() {
                        recalculateOrderNumbers();
                        saveOrder(true);
                    }
                });
            }

            function recalculateOrderNumbers() {
                $('#sortable-tbody .sortable-row').each(function(index) {
                    var newOrder = index + 1;
                    $(this).find('.order-number').text(newOrder);
                    $(this).find('.slab-order-val').val(newOrder);
                });
            }

            function saveOrder(isAuto) {
                var orders = [];
                $('#sortable-tbody .sortable-row').each(function(index) {
                    orders.push({
                        id: $(this).data('id'),
                        order_by: index + 1
                    });
                });

                if (orders.length === 0) return;

                if (!isAuto) {
                    $('#btn-save-order').prop('disabled', true);
                    $('#save-btn-text').text('Saving...');
                }

                $.ajax({
                    url: `{{ route('product-slab-type.order.update') }}`,
                    type: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    data: {
                        orders: orders
                    },
                    success: function(response) {
                        if (!isAuto) {
                            $('#btn-save-order').prop('disabled', false);
                            $('#save-btn-text').text('Save Order');
                            if (typeof Swal !== 'undefined') {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Success!',
                                    text: response.message || 'Order updated successfully.',
                                    timer: 1500,
                                    showConfirmButton: false
                                });
                            } else if (typeof toastr !== 'undefined') {
                                toastr.success(response.message || 'Order updated successfully.');
                            }
                        } else {
                            $('#save-status').removeClass('d-none').fadeIn();
                            setTimeout(function() {
                                $('#save-status').fadeOut();
                            }, 2500);
                        }
                    },
                    error: function(xhr) {
                        if (!isAuto) {
                            $('#btn-save-order').prop('disabled', false);
                            $('#save-btn-text').text('Save Order');
                        }
                        var errorMsg = 'Failed to save order. Please try again.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMsg = xhr.responseJSON.message;
                        }
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: errorMsg
                            });
                        } else if (typeof toastr !== 'undefined') {
                            toastr.error(errorMsg);
                        }
                    }
                });
            }

            $('#btn-save-order').click(function(e) {
                e.preventDefault();
                saveOrder(false);
            });
        });
    </script>
@endsection
