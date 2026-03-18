<?php

namespace Database\Seeders;

use App\Models\Master\Category;
use App\Models\Master\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductSeeder extends Seeder
{
    public function run()
    {

         $category = \App\Models\Category::firstOrCreate(
                ['name' => 'Bags'],
                [
                    'name'=>'Bags',  
                    'is_protected'=>'yes',
                    'company_id' => 1

                ]
            );

            $category_names = [
            'P.P',
            'Jute',
            'Cotton',
            'Laminated',
            'Gazetted',
            'Inner P.P',
            'Inner Jute',
            'Polythene',
            'P Liner',
            'Jar',
            'WHITE PAPER BAG',
            'ONE SIDE BOPP LAMINATED BAG',
            'JAMBO BAGS',
            'SINGLE WOVEN POLYPROPLENE',
            'BULK',
            'PLASTIC BAG',
            'CARTON',
            'LINER BAGS',
            'NEW SINGLE TRANSPARENT POLYPROPYLENE',
            'INNER POLYTHENE',
            'OUTER PP BAGS',
            'NON WOVEN',
            'MASTER BAGS',
            'PE 3 Side Seal',
            'ONE SIDE LAMINATED',
            'RIVIANA BRAND',
        ];

        foreach($category_names as $product){
            \App\Models\Product::firstOrCreate(
                ['name' => $product],
                [
                    'name'=>$product,  
                    'category_id'=>$category->id,
                    'product_type'=>'general_items',
                    'company_id' => 1
                ]
            );
        }
       

    }
}
