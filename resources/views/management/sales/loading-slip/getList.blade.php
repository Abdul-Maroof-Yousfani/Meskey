@if($LoadingSlips->count() > 0)
    <table class="table m-0">
        <tbody>
            @foreach($LoadingSlips as $loadingSlip)
                <tr>
                    <td>
                        {{ $loadingSlip->loadingProgramItem->transaction_number ?? 'N/A' }}
                    </td>
                    <td>
                        {{ $loadingSlip->loadingProgramItem->truck_number ?? 'N/A' }}
                    </td>
                    <td>
                        {{ $loadingSlip->customer ?? 'N/A' }}
                    </td>
                    <td>
                        {{ $loadingSlip->commodity ?? 'N/A' }}
                    </td>
                    <td>
                        {{ $loadingSlip->no_of_bags }}
                    </td>
                    <td>
                        {{ number_format($loadingSlip->kilogram, 2) }}
                    </td>
                    <td>
                        {{ $loadingSlip->created_at->format('d-m-Y H:i') }}
                    </td>
                    <td>
                        <a onclick="openModal(this,'{{ route('sales.loading-slip.edit', $loadingSlip->id) }}','Edit Loading Slip', false)"
                            class="warning p-1 text-center mr-2 position-relative">
                            <i class="ft-edit font-medium-3"></i>
                        </a>
                        <a onclick="openModal(this,'{{ route('sales.loading-slip.show', $loadingSlip->id) }}','View Loading Slip', true)"
                            class="info p-1 text-center mr-2 position-relative">
                            <i class="ft-eye font-medium-3"></i>
                        </a>
                        <a onclick="deletemodal('{{ route('sales.loading-slip.destroy', $loadingSlip->id) }}', '{{ route('sales.get.loading-slip') }}')"
                            class="danger p-1 text-center mr-2 position-relative">
                             <i class="ft-trash-2"></i>
                         </a>

                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Pagination -->
    <div class="d-flex justify-content-end mt-3">
        {{ $LoadingSlips->appends(request()->query())->links() }}
    </div>
@else
    <table class="table m-0">
        <tbody>
            <tr>
                <td colspan="8" class="text-center py-5">
                    <h5 class="text-muted">No Loading Slip records found</h5>
                </td>
            </tr>
        </tbody>
    </table>
@endif
