<div class="row">
    <div class="col-xs-12 col-sm-12 col-md-12" bis_skin_checked="1">
        <div class="form-group " bis_skin_checked="1">
            <label data-name="QC Product">Commodity:</label>
            <input type="text" value="{{optional($initialRequestForInnerReq->arrivalProduct)->name ?? 'Undeifined'}}" disabled=""
                placeholder="Qc Product" class="form-control" autocomplete="off">
        </div>
    </div>
    <div class="col-12">
        <h6 class="header-heading-sepration">
            QC Checklist
        </h6>
    </div>
</div>

<div class="row w-100 mx-auto">
    <div class="col-md-4"></div>
    <div class="col-md-3 py-2 QcResult">
        <h6>Result</h6>
    </div>
</div>
<div class="striped-rows">
    @if (count($initialRequestResults) != 0)
        @foreach ($initialRequestResults as $slab)
            <?php
                $getDeductionSuggestion = getDeductionSuggestion($slab->slabType->id, optional($initialRequestForInnerReq->arrivalTicket)->qc_product, $slab->checklist_value);
                            ?>
            <div class="form-group row">
                <input type="hidden" name="initial_product_slab_type_id[]" value="{{ $slab->slabType->id }}">
                <label class="col-md-4 label-control font-weight-bold" for="striped-form-1">{{ $slab->slabType->name }}</label>
                <div class="col-md-8 QcResult">
                    <input type="text" id="striped-form-1" readonly class="form-control" name="initial_checklist_value[]"
                        value="{{ $slab->checklist_value }}" placeholder="%">
                </div>
            </div>
        @endforeach
    @else
        <div class="alert alert-warning">
            No Initial Slabs Found
        </div>
    @endif
</div>
<br>
<div class="row w-100 mx-auto">
    <div class="col-md-4"></div>
    <div class="col-md-6 py-2 QcResult">
        <h6>Result</h6>
    </div>
</div>
<div class="striped-rows">
    @if (count($initialRequestCompulsuryResults) != 0)
        @foreach ($initialRequestCompulsuryResults as $slab)
            <div class="form-group row">
                <input type="hidden" name="initial_compulsory_param_id[]" value="{{ $slab->qcParam->id }}">
                <label class="col-md-4 label-control font-weight-bold" for="striped-form-1">{{ $slab->qcParam->name }}</label>
                <div class="col-md-8 QcResult">
                    @if ($slab->qcParam->type == 'dropdown')
                        <input type="text" id="striped-form-1" readonly class="form-control"
                            name="initial_compulsory_checklist_value[]" value="{{ $slab->compulsory_checklist_value }}"
                            placeholder="%">
                    @else
                        <textarea type="text" id="striped-form-1" readonly class="form-control"
                            name="initial_compulsory_checklist_value[]"
                            placeholder="%"> {{ $slab->compulsory_checklist_value }}</textarea>
                    @endif
                </div>
            </div>
        @endforeach
    @else
        <div class="alert alert-warning">
            No Initial Compulsory Slabs Found
        </div>
    @endif
</div>
<div class="row mt-3">
    <div class="col-12">
        <h6 class="header-heading-sepration">
            Other Details
        </h6>
    </div>
    <div class="col-12 px-3">
        <div class="form-group ">
            <label>Sample Taken By:</label>
            <select name="sample_taken_by" id="sample_taken_by" class="form-control select2" disabled>
                <option value="">Sample Taken By</option>
                @foreach ($sampleTakenByUsers as $sampleTakenUser)
                    <option @selected($initialRequestForInnerReq->sample_taken_by == $sampleTakenUser->id)
                        value="{{ $sampleTakenUser->id }}">
                        {{ $sampleTakenUser->name }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="col-12 px-3">
        <div class="form-group ">
            <label>Sample Analysis By: </label>
            <input type="text" readonly disabled name="sample_analysis_by" placeholder="Sample Analysis By"
                class="form-control" autocomplete="off" value="{{ auth()->user()->name ?? '' }}" />
        </div>
    </div>
    <div class="col-12 px-3">
        <div class="form-group ">
            <label>Party Ref. No: </label>
            <select name="party_ref_no" id="party_ref_no" class="form-control select2" disabled>
                <option value="{{ $initialRequestForInnerReq->party_ref_no }}">
                    {{ $initialRequestForInnerReq->party_ref_no }}
                </option>
            </select>
        </div>
    </div>
</div>