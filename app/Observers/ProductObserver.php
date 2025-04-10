<?php

namespace App\Observers;

use App\Models\Product;
use Illuminate\Support\Str;

class ProductObserver
{
    /**
     * Handle the User "created" event.
     */
    public function creating(Product $product)
    {
        $product->unique_no  =  generateUniqueNumber('products', null, null, 'unique_no');
        if (request()->hasFile('image')) {
            $product->image = 'storage/' . $this->storeProfileImage(request()->file('image'), $product);
        }
    }
    public function saving(Product $product)
    {
        if (request()->hasFile('image')) {
            $product->image = 'storage/' . $this->storeProfileImage(request()->file('image'), $product);
        }
    }

    protected function storeProfileImage($image, Product $product)
    {

        $logoOriginalName = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);

        $sluggedName = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $logoOriginalName));
        $filename = 'product-' . Str::slug($logoOriginalName) . '-' . now()->format('YmdHis') . '.' . $image->getClientOriginalExtension();

        return $image->storeAs('images', $filename, 'public');
    }
}
