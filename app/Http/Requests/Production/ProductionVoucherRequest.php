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
                'nullable',
                'exists:plants,id'
            ],
            'by_product_id' => [
                'nullable',
                'exists:products,id'
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
                    $voucherId = $this->route('production_voucher');

                    $voucherHasTimes = false;
                    if ($voucherId) {
                        $pv = \App\Models\Production\ProductionVoucher::with('productionVoucherMachineTimes')->find($voucherId);
                        $voucherHasTimes = $pv && $pv->plant_id == $plantId && $pv->productionVoucherMachineTimes->isNotEmpty();
                    }

                    if (!$voucherHasTimes) {
                        $plan = \App\Models\Production\MachinePlanSetting::with('items')
                            ->where('plant_id', $plantId)
                            ->whereDate('date', $prodDate)
                            ->when($voucherId, function ($q) use ($voucherId) {
                                $q->orWhere('production_voucher_id', $voucherId);
                            })
                            ->first();

                        $formattedDate = \Carbon\Carbon::parse($prodDate)->format('d-m-Y');

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
                }
            }
        });
    }

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
            'plant_id.exists' => 'Selected plant does not exist',
            'by_product_id.exists' => 'Selected by product does not exist',
            'net_total_output.lte' => 'Total input must be greater than or equal to total output.',
        ];
    }
}
