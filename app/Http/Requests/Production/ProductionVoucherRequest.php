<?php

namespace App\Http\Requests\Production;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductionVoucherRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('job_order_id')) {
            $raw = $this->job_order_id;
            $filtered = is_array($raw)
                ? array_values(array_filter($raw, fn($val) => $val !== null && $val !== ''))
                : ($raw ? [$raw] : []);
            $this->merge([
                'job_order_id' => $filtered
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $productionVoucherId = $this->route('production_voucher');
        $isUpdate = $this->isMethod('PUT') || $this->isMethod('PATCH');

        $rules = [
            'prod_date' => [
                'required',
                'date',
                'before_or_equal:today'
            ],
            'job_order_id' => [
                'required',
                'array',
                'min:1'
            ],
            'job_order_id.*' => [
                'exists:job_orders,id'
            ],
            'location_id' => [
                'required',
                'exists:company_locations,id'
            ],
            'sub_location_id' => [
                'required',
                'exists:arrival_sub_locations,id'
            ],
            'product_id' => [
                'required',
                'exists:products,id'
            ],
            'plant_id' => [
                'required',
                'exists:plants,id'
            ],
            'by_product_id' => [
                'nullable',
                'exists:products,id'
            ],
            'machine_plan_setting_id' => [
                'nullable',
                'integer'
            ],
            'remarks' => [
                'nullable',
                // 'string',
                'max:1000'
            ],
            'labour_deduction' => [
                'nullable',
                'numeric',
                'min:0'
            ],
            'labour_deduction_remarks' => [
                'nullable',
                'string',
                'max:1000',
                Rule::requiredIf($this->labour_deduction > 0)
            ],
            'net_total_input' => [
                'required',
                'numeric',
                'min:0'
            ],
            'net_total_output' => [
                'required',
                'numeric',
                'min:0',
            ],
            // Production Inputs (using input_ prefix to avoid conflict with main form fields)
            'input_product_id.*' => [
                'nullable',
                'exists:products,id'
            ],
            'input_location_id.*' => [
                'nullable',
                'exists:arrival_sub_locations,id'
            ],
            'input_qty.*' => [
                'nullable',
                'numeric',
                'min:0.01'
            ],
            'input_remarks.*' => [
                'nullable',
                'string',
                'max:1000'
            ],
            // Production Outputs
            'output_product_id.*' => [
                'nullable',
                'exists:products,id'
            ],
            'output_qty.*' => [
                'nullable',
                'numeric',
                'min:0.01'
            ],
            'output_no_of_bags.*' => [
                'nullable',
                'integer',
                'min:0'
            ],
            'output_bag_size.*' => [
                'nullable',
                'string',
                'max:50'
            ],
            'output_avg_weight_per_bag.*' => [
                'nullable',
                'numeric',
                'min:0'
            ],
            'output_yield.*' => [
                'nullable',
                'numeric',
                'min:0'
            ],
            'output_arrival_sub_location_id.*' => [
                'nullable',
                'exists:arrival_sub_locations,id'
            ],
            'output_brand_id.*' => [
                'nullable',
                'exists:brands,id'
            ],
            'output_job_order_id.*' => [
                'nullable',
                'exists:job_orders,id'
            ],
            'output_remarks.*' => [
                'nullable',
                'string',
                'max:1000'
            ],
            'production_machine_id' => [
                'nullable',
                'array'
            ],
            'production_machine_id.*' => [
                'exists:production_machines,id'
            ],
            'breakdown_type_id' => [
                'nullable',
                'array'
            ],
            'from' => [
                'nullable',
                'array'
            ],
            'to' => [
                'nullable',
                'array'
            ],
            'hours' => [
                'nullable',
                'array'
            ],
            'breakdown_remarks' => [
                'nullable',
                'array'
            ]
        ];

        // Only require prod_no on create
        if (!$isUpdate) {
            $rules['prod_no'] = [
                'nullable',
                'string',
                'max:100',
                Rule::unique('production_vouchers', 'prod_no')
            ];
        }

        return $rules;
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $outputQtys = $this->input('output_qty', []);
            $outputNoOfBags = $this->input('output_no_of_bags', []);
            $outputProductIds = $this->input('output_product_id', []);
            $outputBagSizes = $this->input('output_bag_size', []);

            $allIndices = array_unique(array_merge(
                array_keys($outputQtys),
                array_keys($outputNoOfBags),
                array_keys($outputBagSizes)
            ));

            // Validate all output rows: if any of No of Bags, Packed in (KG), Qty (kg) is entered, all three are required
            foreach ($allIndices as $index) {
                $bags = $outputNoOfBags[$index] ?? null;
                $size = $outputBagSizes[$index] ?? null;
                $qty = $outputQtys[$index] ?? null;

                $hasBags = (!is_null($bags) && $bags !== '' && (float)$bags > 0);
                $hasSize = (!is_null($size) && $size !== '' && (float)$size > 0);
                $hasQty = (!is_null($qty) && $qty !== '' && (float)$qty > 0);

                if ($hasBags || $hasSize || $hasQty) {
                    $prodName = 'Row ' . ($index + 1);
                    if (!empty($outputProductIds[$index])) {
                        $prod = \App\Models\Product::find($outputProductIds[$index]);
                        if ($prod) {
                            $prodName = $prod->name;
                        }
                    }

                    if (!$hasBags) {
                        $validator->errors()->add("output_no_of_bags.{$index}", "No of Bags is required for {$prodName} when Packed in (KG) or Qty (kg) is entered.");
                    }

                    if (!$hasSize) {
                        $validator->errors()->add("output_bag_size.{$index}", "Packed in (KG) is required for {$prodName} when No of Bags or Qty (kg) is entered.");
                    }

                    if (!$hasQty) {
                        $validator->errors()->add("output_qty.{$index}", "Qty (kg) is required for {$prodName} when No of Bags or Packed in (KG) is entered.");
                    }
                }
            }

            // Validate that Total Input >= Total Output
            $netTotalInput = (float)$this->input('net_total_input', 0);
            $netTotalOutput = (float)$this->input('net_total_output', 0);

            if ($netTotalOutput > $netTotalInput) {
                $formattedOutput = number_format($netTotalOutput, 2);
                $validator->errors()->add(
                    'net_total_output',
                    "Total input must be greater than or equal to total output (Total Output: {$formattedOutput} KG)."
                );
            }

            // Validate that Plant has active machines AND Machine Plan Setting record for this date
            if ($this->filled('plant_id')) {
                $plantId = $this->plant_id;
                $plant = \App\Models\Master\Plant::find($plantId);
                $plantName = $plant ? $plant->name : 'selected plant';

                $hasActiveMachines = \App\Models\Master\ProductionMachine::where('plant_id', $plantId)
                    ->where('status', 'active')
                    ->exists();

                if (!$hasActiveMachines) {
                    $validator->errors()->add(
                        'plant_id',
                        "No active Production Machines found for '{$plantName}'. At least one active Production Machine record must exist for this plant."
                    );
                } else if ($this->filled('prod_date')) {
                    $prodDate = $this->prod_date;
                    $voucherId = $this->voucherId();
                    $formattedDate = \Carbon\Carbon::parse($prodDate)->format('d-m-Y');

                    $voucherHasTimes = false;
                    if ($voucherId) {
                        $pv = \App\Models\Production\ProductionVoucher::with('productionVoucherMachineTimes')->find($voucherId);
                        $voucherHasTimes = $pv && $pv->plant_id == $plantId && $pv->productionVoucherMachineTimes->isNotEmpty();
                    }

                    // Which of the day's plans this voucher belongs to (one voucher per plan)
                    $choice = $this->planChoice();
                    $plan = $choice['plan'];

                    if ($choice['error']) {
                        $validator->errors()->add('machine_plan_setting_id', $choice['error']);
                    } elseif (!$voucherHasTimes) {
                        if (!$plan) {
                            $validator->errors()->add(
                                'plant_id',
                                "No Machine Plan Setting record found for plant '{$plantName}' on {$formattedDate}. Please create a Machine Plan Setting for this plant and date first."
                            );
                        } else if (!$plan->items || $plan->items->isEmpty()) {
                            $validator->errors()->add(
                                'plant_id',
                                "Machine Plan Setting for plant '{$plantName}' on {$formattedDate} has no machine records. Please configure at least one machine in Machine Plan Setting."
                            );
                        }
                    }

                    // Machine times must not overlap each other or the plant's other plans / vouchers that day
                    if (!$choice['error']) {
                        $conflicts = app(\App\Services\Production\Milling\ProductionDayPlanService::class)->timeConflicts(
                            (int) $plantId,
                            $prodDate,
                            \App\Services\Production\Milling\ProductionDayPlanService::slotsFromRequest($this, false),
                            $plan?->id,
                            array_filter([$voucherId, $plan?->production_voucher_id])
                        );
                        foreach (array_slice($conflicts, 0, 6) as $message) {
                            $validator->errors()->add('machine_times', $message);
                        }
                    }
                }
            }

            // Costing needs raw material in and finished goods out
            $this->validateInputAndHeadOutput($validator);
        });
    }

    /** At least one complete input row and one head product output row. */
    private function validateInputAndHeadOutput($validator): void
    {
        $inputProductIds = (array) $this->input('input_product_id', []);
        $inputLocationIds = (array) $this->input('input_location_id', []);
        $inputQtys = (array) $this->input('input_qty', []);

        $hasInput = false;
        foreach ($inputProductIds as $i => $productId) {
            if (!empty($productId) && !empty($inputLocationIds[$i]) && (float) ($inputQtys[$i] ?? 0) > 0) {
                $hasInput = true;
                break;
            }
        }
        if (!$hasInput) {
            $validator->errors()->add('input_product_id', 'At least one Production Input (product, location and qty) is required.');
        }

        $outputProductIds = (array) $this->input('output_product_id', []);
        $outputQtys = (array) $this->input('output_qty', []);
        $outputBags = (array) $this->input('output_no_of_bags', []);
        $outputSizes = (array) $this->input('output_bag_size', []);
        $headProductId = $this->input('product_id');

        $rowProductIds = [];
        foreach ($outputProductIds as $i => $productId) {
            $qty = (float) ($outputQtys[$i] ?? 0);
            if ($qty <= 0) {
                $qty = (float) ($outputBags[$i] ?? 0) * (float) ($outputSizes[$i] ?? 0);
            }
            if (!empty($productId) && $qty > 0) {
                $rowProductIds[] = $productId;
            }
        }

        // Same rule as the costing: the voucher's head product, or a 'head' product that is not a By / B2 product
        $flags = \App\Models\Product::whereIn('id', array_unique($rowProductIds))->pluck('product_category_flags', 'id');
        $hasHead = collect($rowProductIds)->contains(function ($productId) use ($headProductId, $flags) {
            return $productId == $headProductId || ($flags[$productId] ?? null) === 'head';
        });

        if (!$hasHead) {
            $validator->errors()->add('output_product_id', 'At least one Head Product output with qty is required; without it the production cost has nowhere to go.');
        }
    }

    /** Id of the voucher being updated (null on create). */
    public function voucherId(): ?int
    {
        $id = $this->route('production_voucher');
        return $id ? (int) (is_object($id) ? $id->id : $id) : null;
    }

    /** The day's Machine Plan this voucher is saved against; see ProductionDayPlanService::planChoiceForVoucher. */
    public function planChoice(): array
    {
        if ($this->planChoiceCache === null) {
            $this->planChoiceCache = app(\App\Services\Production\Milling\ProductionDayPlanService::class)->planChoiceForVoucher(
                $this->plant_id ? (int) $this->plant_id : null,
                $this->prod_date,
                $this->filled('machine_plan_setting_id') ? (int) $this->machine_plan_setting_id : null,
                $this->voucherId()
            );
        }
        return $this->planChoiceCache;
    }

    private ?array $planChoiceCache = null;

    /**
     * Get custom error messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'prod_no.unique' => 'This production number already exists',
            'prod_date.required' => 'Production date is required',
            'prod_date.before_or_equal' => 'Production date cannot be in the future',
            'job_order_id.required' => 'Job order is required',
            'job_order_id.min' => 'At least one job order is required',
            'job_order_id.*.exists' => 'Selected job order does not exist',
            'location_id.required' => 'Location is required',
            'location_id.exists' => 'Selected location does not exist',
            'product_id.required' => 'Head product is required',
            'product_id.exists' => 'Selected head product does not exist',
            'plant_id.required' => 'Plant is required',
            'plant_id.exists' => 'Selected plant does not exist',
            'by_product_id.exists' => 'Selected by product does not exist',
            'net_total_output.lte' => 'Total input must be greater than or equal to total output.',
        ];
    }
}
