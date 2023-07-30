<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;
use Illuminate\Support\Str;
use Faker\Factory as Faker;

class AssistantProductSeeder extends Seeder
{

    const BRANDS = [];

    /**
     * Run the database seeds.
     *
     * @return void
     */

    public function run()
    {
        //php artisan db:seed --force --class=AssistantProductSeeder

        $config = config('modules-assistant-seeder.seeders.seeds.products', []);

        if (empty($config)) {
            exit(0);
        }

        $db = DB::connection('core_mysql');

        $multiTenant = env('DORCAS_EDITION', 'business') != 'business';

        if (!$multiTenant) {
            $defaultUsers = $db->table("users")->first();
        } else {
            $defaultUsers = $db->table("users")->orderBy('created_at','asc')->get();
        }

        if (!empty($config["users"]) && $multiTenant ) {
            $defaultUsers->limit($config["users"]);
        }

        dd($defaultUsers);
        
        foreach ($defaultUsers as $user) {

            dd($user);

            $faker = Faker::create();

            $company = $user['company_id'];
            # get the company

            //$categories = $company->productCategories();
            $categories = $db->table("product_categories")->get();
            # check if categories exist

            if (empty($categories->count())) {
                $category_name = "Default";
                $slug = $company->id . '-' . Str::slug($category_name);
                # set the slug
                if ($db->table("product_categories")->where('slug', $slug)->count() > 0) {
                    $slug .= '-' . uniqid();
                }
                // $category = $company->productCategories()->create([
                //     'name' => $category_name,
                //     'slug' => $slug,
                //     'description' => 'Default Product Category'
                // ]);
                //insertGetId
                $category = $db->table("product_categories")->insert([
                    'uuid' => (string) \Illuminate\Support\Str::uuid(),
                    'company_id' => $company->id,
                    'name' => $category_name,
                    'slug' => $slug,
                    'description' => 'Default Product Category'
                ]);

            } else {
                $category = $categories->first();
            }
            dd($category);

            // Create Products
            for ($i = 0; $i <= $config["count"]; $i++) {

                $amount = rand(2000, 10000);
                $stock = rand(4, 20);
                $image = $faker->imageUrl(360, 360, 'electronics', true, 'cats');

                // $product = $company->products()->create([
                //     'name' => $faker->word,
                //     'description' => $faker->sentence,
                //     'product_type' => 'default',
                //     'unit_price' => $amount
                // ]);
                $product = $db->table("product_categories")->insert([
                    'uuid' => (string) \Illuminate\Support\Str::uuid(),
                    'company_id' => $company->id,
                    'name' => $faker->word,
                    'description' => $faker->sentence,
                    'product_type' => 'default',
                    'unit_price' => $amount
                ]);

                # create product

                $productPrices = collect([]);
                # product price container

                $productPrices[] = ['currency' => env('SETTINGS_CURRENCY', 'NGN'), 'unit_price' => $amount];
                # add the price to the array

                $product->prices()->createMany($productPrices);
                # update product price

                $categories = ProductCategory::on('core_mysql')->where('uuid', $category->uuid)->pluck('id');

                $product->categories()->sync($categories);
                # update product category

                $product->stocks()->create(['action' => 'add', 'quantity' => $stock, 'comment' => 'Default Stock']);
                # update product stock

                $product->update(['inventory' => $stock]);
                # update product stock

                if (!empty($image)) {
                    $product->images()->create(['url' => $image]);
                }
                # update product image

            }

            

        }

        
   
    }
}