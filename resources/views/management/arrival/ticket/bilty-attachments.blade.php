<div class="row g-4">
    {{-- Return Reason --}}
    @if ($ticket->bilty_return_reason)


        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group ">
                <label>Return Reason:</label>
                <textarea readonly rows="4" class="form-control"
                    placeholder="Description"> {{ $ticket->bilty_return_reason }} </textarea>
            </div>
        </div>
    @endif

    {{-- Attachments --}}
    @php
        $hasAttachments = $ticket->bilty_return_attachment || $ticket->weightslip_attachment || $ticket->other_attachment;
    @endphp

    @if ($hasAttachments)
        <div class="col-12">
            <h5 class="mb-3">Attachments</h5>
            <div class="row g-3">
                {{-- Bilty Return Attachment --}}
                @if ($ticket->bilty_return_attachment)
                    <div class="col-md-4 col-sm-6">
                        <div class="card shadow-sm h-100">
                            <div class="card-body p-2">
                                <div class="attachment-wrapper">
                                    <a href="{{ asset($ticket->bilty_return_attachment) }}" target="_blank" class="d-block">
                                        <img src="{{ asset($ticket->bilty_return_attachment) }}" alt="Bilty Attachment"
                                            class="img-fluid rounded" style="width: 100%; height: 150px; object-fit: cover;" />
                                    </a>
                                </div>
                                <p class="text-center mt-2 mb-0 small">
                                    Bilty Return
                                </p>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Weightslip Attachment --}}
                @if ($ticket->weightslip_attachment)
                    <div class="col-md-4 col-sm-6">
                        <div class="card shadow-sm h-100">
                            <div class="card-body p-2">
                                <div class="attachment-wrapper">
                                    <a href="{{ asset($ticket->weightslip_attachment) }}" target="_blank" class="d-block">
                                        <img src="{{ asset($ticket->weightslip_attachment) }}" alt="Weightslip Attachment"
                                            class="img-fluid rounded" style="width: 100%; height: 150px; object-fit: cover;" />
                                    </a>
                                </div>
                                <p class="text-center mt-2 mb-0 small">
                                    Weightslip
                                </p>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Other Attachment --}}
                @if ($ticket->other_attachment)
                    <div class="col-md-4 col-sm-6">
                        <div class="card shadow-sm h-100">
                            <div class="card-body p-2">
                                <div class="attachment-wrapper">
                                    <a href="{{ asset($ticket->other_attachment) }}" target="_blank" class="d-block">
                                        <img src="{{ asset($ticket->other_attachment) }}" alt="Other Attachment"
                                            class="img-fluid rounded" style="width: 100%; height: 150px; object-fit: cover;" />
                                    </a>
                                </div>
                                <p class="text-center mt-2 mb-0 small">
                                    Other
                                </p>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>