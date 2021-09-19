<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class ImportJson extends Command
{
    const CATEGORIES_JSON_PATH = '/app/categories.json';
    const PRODUCTS_JSON_PATH = '/app/products.json';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:json';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import data from json and save in DB';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->importCategories();
        $this->importProducts();
    }

    /**
     * Import categories from categories.json
     */
    public function importCategories()
    {
        $this->info('Started Importing Categories');
        $json_data = json_decode(file_get_contents(storage_path(self::CATEGORIES_JSON_PATH)));
        $bar = $this->output->createProgressBar(count($json_data));
        foreach ($json_data as $item)
        {
            $data = [
                'eId' => $item->eId,
                'title' => $item->title
            ];
            if($this->validateCategoriesData($data)){
                Category::create($data);
            }
            $bar->advance();
        }
        $bar->finish();
        $this->info("\n" . 'Categories Imported');
    }

    /**
     * Import products from products.json and set product_categories relations
     */
    public function importProducts()
    {
        $this->info('Started Importing Products');
        $json_data = json_decode(file_get_contents(storage_path(self::PRODUCTS_JSON_PATH)));
        $bar = $this->output->createProgressBar(count($json_data));
        foreach ($json_data as $item)
        {
            $data = [
                'eId' => $item->eId,
                'title' => $item->title,
                'price' => $item->price
            ];
            if($this->validateProductData($data)){
                $product = Product::create($data);
                $categories_ids = Category::whereIn('eId', $item->categoriesEId ?? $item->categoryEId) // In json exist this two keys
                    ->pluck('id')
                    ->toArray();
                $product->categories()->sync($categories_ids);
            }
            $bar->advance();
        }
        $bar->finish();
        $this->info("\n" . 'Products Imported');
    }


    /**
     * @param $data
     * @return bool
     */
    public function validateCategoriesData($data)
    {
        $validator = Validator::make($data, [
            'title' => 'required|string|min:3|max:12',
            'eId' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            // $validator->errors()   for show More detailed validation errors
            $this->error("\n" . $data['eId'] . ' Category did not pass validation');
            return false;
        }

        return true;

    }

    /**
     * @param $data
     * @return bool
     */
    public function validateProductData($data)
    {
        $validator = Validator::make($data, [
            'title' => 'required|string|min:3|max:12',
            'eId' => 'nullable|integer',
            'price' => 'required|numeric|between:0,200',
        ]);

        if ($validator->fails()) {
            // $validator->errors()   for show More detailed validation errors
            $this->error("\n" . $data['eId'] . ' Product did not pass validation');
            return false;
        }

        return true;

    }
}
