<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
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

        $category_ids = [];

        foreach ($category_names as $name) {
            $category = Category::firstOrCreate(
                ['name' => $name],
            );

            $category_ids[] = $category->id;

            $category->is_protected = "yes";
            $category->save();
        }

        $existingCategoryIds = DB::table('products')
            ->whereIn('category_id', $category_ids)
            ->pluck('category_id')
            ->toArray();

        $newCategoryIds = array_diff($category_ids, $existingCategoryIds);

      
        $products = Product::latest()->first();
        $next_unique_no = (int)$products->unique_no;

        $products = array_map(fn ($id) => [
            'category_id' => $id,
            'company_id' => 1,
            'unique_no' => "-",
            'name' => '-'
        ], $newCategoryIds);

        
        if (!empty($products)) {
            DB::table('products')->insert($products);
        }

    }
}
