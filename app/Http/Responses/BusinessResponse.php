<?php

namespace App\Http\Responses;

use Illuminate\Http\Resources\Json\JsonResource;

class BusinessResponse extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->business_name,
            'prefix' => $this->business_prefix,
            'registration_no' => $this->registration_no,
            'business_logo' => $this->business_logo ? asset('storage/' . $this->business_logo) : null,
            'photos' => $this->photos->map(
                fn($photo) => [
                    'id' => $photo->id,
                    'url' => asset('storage/' . $photo->photo_path),
                ],
            ),
            'address' => $this->address,
            'phone_no' => $this->phone_no,
            'whats_app_no' => $this->whats_app_no,
            'country_id' => $this->country_id, 
            'flat_shop_number' => $this->flat_shop_number, 
            'building_no' => $this->building_no, 
            'road_no' => $this->road_no, 
            'block_no' => $this->block_no, 
            'city' => $this->city, 
            'is_tax_enable' => $this->is_tax_enable, 
            'tax' => $this->tax, 
            'defaultCurrency' => $this->defaultCurrency->currency,
            'country' => $this->country,
        ];
    }
}
