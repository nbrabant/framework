<?php

namespace AvoRed\Framework\Models\Database;

use Illuminate\Support\Facades\Session;
use AvoRed\Framework\Image\LocalFile;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use AvoRed\Ecommerce\Models\Database\Configuration;

class Product extends Model
{
    protected $fillable = ['type', 'name', 'slug', 'sku', 'description',
        'status', 'in_stock', 'track_stock', 'qty',
        'is_taxable', 'meta_title', 'meta_description',
        'weight', 'width', 'height', 'length'
    ];

    public function getCollection()
    {
        $model = new static;
        $products = $model->all();
        $productCollection = new ProductCollection();
        $productCollection->setCollection($products);
        return $productCollection;
    }



    public static function boot()
    {
        parent::boot();

        // registering a callback to be executed upon the creation of an activity AR
        static::creating(function ($model) {

            // produce a slug based on the activity title
            $slug = Str::slug($model->name);

            // check to see if any other slugs exist that are the same & count them
            $count = static::where("slug", "=", $slug)->count();

            // if other slugs exist that are the same, append the count to the slug
            $model->slug = $count ? "{$slug}-{$count}" : $slug;

        });

    }

    public function hasVariation() {
        if($this->type == 'VARIATION') {
            return true;
        }

        return false;
    }

    public function canAddtoCart($qty = 0)
    {
        $products = Session::get('cart');

        if (null == $products) {
            return true;
        }

        $productId = $this->attributes['id'];

        $cartProduct = $products->get($productId);

        $availableQty = $this->attributes['qty'];

        $currentCartQty = (isset($cartProduct['qty'])) ? $cartProduct['qty'] : 0;

        if ($availableQty - $currentCartQty - $qty < 0) {
            return false;
        } else {
            return true;
        }

    }

    /**
     * Update the Product and Product Related Data
     *
     * @var \AvoRed\Ecommerce\Http\Requests\ProductRequest $request
     * @return void
     */
    public function saveProduct($request)
    {

        //*****  SAVING PRODUCT BASIC FIELDS  *****//
        $this->update($request->all());


        //*****  SAVING PRODUCT PRICES  *****//
        if ($this->prices()->get()->count() > 0) {
            $this->prices()->get()->first()->update(['price' => $request->get('price')]);
        } else {
            $this->prices()->create(['price' => $request->get('price')]);
        }

        //*****  SAVING PRODUCT IMAGES  *****//
        if (null !== $request->get('image')) {
            $exitingIds = $this->images()->get()->pluck('id')->toArray();
            foreach ($request->get('image') as $key => $data) {
                if (is_int($key)) {
                    if (($findKey = array_search($key, $exitingIds)) !== false) {
                        $productImage = ProductImage::findorfail($key);
                        $productImage->update($data);
                        unset($exitingIds[$findKey]);
                    }
                    continue;
                }
                ProductImage::create($data + ['product_id' => $this->id]);
            }
            if (count($exitingIds) > 0) {
                ProductImage::destroy($exitingIds);
            }
        }

        //*****  SAVING PRODUCT CATEGORIES  *****//
        if (count($request->get('category_id')) > 0) {
            $this->categories()->sync($request->get('category_id'));
        }


        $properties = $request->get('property');

        if (null !== $properties && count($properties) > 0) {


            foreach ($properties as $key => $property) {

                foreach ($property as $propertyId => $propertyValue) {

                    $propertyModal = Property::findorfail($propertyId);

                    if ($propertyModal->data_type == 'VARCHAR') {

                        $propertyVarcharValue = ProductPropertyVarcharValue::whereProductId($this->id)->wherePropertyId($propertyId)->get()->first();

                        if (null === $propertyVarcharValue) {
                            ProductPropertyVarcharValue::create([
                                'product_id' => $this->id,
                                'property_id' => $propertyId,
                                'value' => $propertyValue
                            ]);
                        } else {
                            $propertyVarcharValue->update(['value' => $propertyValue]);
                        }
                    }

                    if ($propertyModal->data_type == 'BOOLEAN') {

                        $propertyBooleanValue = ProductPropertyBooleanValue::whereProductId($this->id)->wherePropertyId($propertyId)->get()->first();

                        if (null === $propertyBooleanValue) {
                            ProductPropertyBooleanValue::create([
                                'product_id' => $this->id,
                                'property_id' => $propertyId,
                                'value' => $propertyValue
                            ]);
                        } else {
                            $propertyBooleanValue->update(['value' => $propertyValue]);
                        }
                    }

                    if ($propertyModal->data_type == 'TEXT') {

                        $propertyTextValue = ProductPropertyTextValue::whereProductId($this->id)->wherePropertyId($propertyId)->get()->first();

                        if (null === $propertyTextValue) {
                            ProductPropertyTextValue::create([
                                'product_id' => $this->id,
                                'property_id' => $propertyId,
                                'value' => $propertyValue
                            ]);
                        } else {
                            $propertyTextValue->update(['value' => $propertyValue]);
                        }
                    }

                    if ($propertyModal->data_type == 'DECIMAL') {

                        $propertyDecimalValue = ProductPropertyDecimalValue::whereProductId($this->id)->wherePropertyId($propertyId)->get()->first();

                        if (null === $propertyDecimalValue) {
                            ProductPropertyDecimalValue::create([
                                'product_id' => $this->id,
                                'property_id' => $propertyId,
                                'value' => $propertyValue
                            ]);
                        } else {
                            $propertyDecimalValue->update(['value' => $propertyValue]);
                        }
                    }
                    if ($propertyModal->data_type == 'INTEGER') {

                        $propertyIntegerValue = ProductPropertyIntegerValue::whereProductId($this->id)->wherePropertyId($propertyId)->get()->first();

                        if (null === $propertyIntegerValue) {
                            ProductPropertyIntegerValue::create([
                                'product_id' => $this->id,
                                'property_id' => $propertyId,
                                'value' => $propertyValue
                            ]);
                        } else {
                            $propertyIntegerValue->update(['value' => $propertyValue]);
                        }
                    }
                    if ($propertyModal->data_type == 'DATETIME') {

                        $propertyDatetimeValue = ProductPropertyDatetimeValue::whereProductId($this->id)->wherePropertyId($propertyId)->get()->first();

                        if (null === $propertyDatetimeValue) {
                            ProductPropertyDatetimeValue::create([
                                'product_id' => $this->id,
                                'property_id' => $propertyId,
                                'value' => $propertyValue
                            ]);
                        } else {
                            $propertyDatetimeValue->update(['value' => $propertyValue]);
                        }
                    }


                }
            }

        }


        $attributeWithOptions = $request->get('attribute');


        if(null !== $attributeWithOptions && count($attributeWithOptions) > 0) {

            $selectedAttributes = $request->get('attribute_selected');

            //$this->attribute()->delete();
            foreach ($selectedAttributes as $selectedAttribute) {
                $this->attribute()->sync($selectedAttribute);
            }


            $optionsArray = [];

            foreach ($attributeWithOptions as $attributeId => $attributeOptions) {
                $optionsArray[] = array_values($attributeOptions);
            }

            $listOfOptions = $this->combinations($optionsArray);


            foreach ($listOfOptions as $option) {


                $variationProductData['name'] = $this->name;
                $variationProductData['type'] = 'VARIABLE_PRODUCT';
                $variationProductData['status'] = 0;
                //$variationProductData['price'] = $this->price;
                $variationProductData['qty'] = $this->qty;


                if(is_array($option)) {
                    foreach ($option as $attributeOptionId) {
                        $attributeOptionModel = AttributeDropdownOption::findorfail($attributeOptionId);
                        $variationProductData['name'] .= " " . $attributeOptionModel->display_text;

                    }
                } else {

                    $attributeOptionModel = AttributeDropdownOption::findorfail($option);
                    $variationProductData['name'] .= " " . $attributeOptionModel->display_text;

                }

                $variationProductData['sku'] = str_slug($variationProductData['name']);
                $variationProductData['slug'] = str_slug($variationProductData['name']);

                $variableProduct = self::create($variationProductData);
                $variableProduct->prices()->create(['price' => $this->price]);



                ProductAttributeIntegerValue::create([
                    'product_id' => $variableProduct->id,
                    'attribute_id' => $attributeOptionModel->attribute->id,
                    'value' => $attributeOptionModel->id
                ]);


                ProductVariation::create(['product_id' => $this->id, 'variation_id' => $variableProduct->id]);

                //@todo Save ATTRIBUTE(PROPERTIES) HERE

            }

        }
        return $this;

    }

    public function combinations($arrays, $i = 0) {
        if (!isset($arrays[$i])) {
            return [];
        }
        if ($i == count($arrays) - 1) {
            return $arrays[$i];
        }


        // get combinations from subsequent arrays
        $tmp = $this->combinations($arrays, $i + 1);

        $result = [];

        // concat each array from tmp with each element from $arrays[$i]
        foreach ($arrays[$i] as $v) {
            foreach ($tmp as $t) {
                $result[] = is_array($t) ?
                    array_merge(array($v), $t) :
                    array($v, $t);

            }
        }

        return $result;
    }

    public static function getProductBySlug($slug)
    {
        $model = new static;
        return $model->where('slug', '=', $slug)->first();
    }

    /**
     * return default Image or LocalFile Object
     *
     * @return \AvoRed\Framework\Image\LocalFile
     */
    public function getImageAttribute()
    {
        $defaultPath = "/img/default-product.jpg";
        $image = $this->images()->where('is_main_image', '=', 1)->first();


        if (null === $image) {
            return new LocalFile($defaultPath);
        }

        if ($image->path instanceof LocalFile) {
            return $image->path;
        }
    }

    /*
     * Calculate Tax amount based on default country and return tax amount
     *
     * @return float $taxAmount
     */

    public function getTaxAmount($price = NULL)
    {
        $percentage = 15; //Configuration->getConfiguratiin();
        if (null === $price) {
            $price = $this->price;
        }

        /*
        if (null === $taxRule) {
            return 0.00;
        }
        */
        $taxAmount = ($percentage * $price / 100);

        return $taxAmount;
    }


    /*
     * Get the Price for the Product
     *
     * @return float $value
     */
    public function getPriceAttribute()
    {
        $row = $this->prices()->first();

        return (isset($row->price)) ? $row->price : null;
    }


    public function categories()
    {
        return $this->belongsToMany(Category::class);
    }

    public function prices()
    {
        return $this->hasMany(ProductPrice::class);
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class);
    }

    public function getProductAllProperties()
    {
        $collection = Collection::make([]);

        foreach ($this->productVarcharProperties as $item) {
            $collection->push($item);
        }

        foreach ($this->productBooleanProperties as $item) {
            $collection->push($item);
        }

        foreach ($this->productTextProperties as $item) {
            $collection->push($item);
        }

        foreach ($this->productDecimalProperties as $item) {
            $collection->push($item);
        }

        foreach ($this->productDecimalProperties as $item) {
            $collection->push($item);
        }

        foreach ($this->productIntegerProperties as $item) {
            $collection->push($item);
        }

        foreach ($this->productDatetimeProperties as $item) {
            $collection->push($item);
        }

        return $collection;
    }


    public function getProductAllAttributes($variation = null)
    {

        if(null === $variation) {
            $variations = $this->productVariations()->get();
        }

        $collection = Collection::make([]);

        if(NULL === $variations || $variations->count() <= 0) {
            return $collection;
        }

        foreach ($variations as $variation) {

            $variationModel = self::findorfail($variation->variation_id);

            foreach ($variationModel->productVarcharAttributes as $item) {
                $collection->push($item);
            }
            foreach ($variationModel->productBooleanAttributes as $item) {
                $collection->push($item);
            }

            foreach ($variationModel->productTextAttributes as $item) {
                $collection->push($item);
            }
            foreach ($variationModel->productDecimalAttributes as $item) {
                $collection->push($item);
            }
            foreach ($variationModel->productDecimalAttributes as $item) {
                $collection->push($item);
            }
            foreach ($variationModel->productIntegerAttributes as $item) {
                $collection->push($item);
            }

            foreach ($variationModel->productDatetimeAttributes as $item) {
                $collection->push($item);
            }

        }

        return $collection;
    }

    public function getVariableProduct($option) {


        $productAttributeIntegerValue = ProductAttributeIntegerValue::whereAttributeId($option->attribute_id)
                                                                        ->whereValue($option->id)->first();


        if(null === $productAttributeIntegerValue) {
            return null;
        }
        return Product::findorfail($productAttributeIntegerValue->product_id);


    }


    public function productVariations()
    {
        return $this->hasMany(ProductVariation::class);
    }


    public function productVarcharAttributes()
    {
        return $this->hasMany(ProductAttributeVarcharValue::class);
    }

    public function productDatetimeAttributes()
    {
        return $this->hasMany(ProductAttributeDatetimeValue::class);
    }

    public function productBooleanAttributes()
    {
        return $this->hasMany(ProductAttributeBooleanValue::class);
    }


    public function productIntegerAttributes()
    {
        return $this->hasMany(ProductAttributeIntegerValue::class);
    }

    public function productTextAttributes()
    {
        return $this->hasMany(ProductAttributeTextValue::class);
    }

    public function productDecimalAttributes()
    {
        return $this->hasMany(ProductAttributeDecimalValue::class);
    }

    public function productVarcharProperties()
    {
        return $this->hasMany(ProductPropertyVarcharValue::class);
    }

    public function productDatetimeProperties()
    {
        return $this->hasMany(ProductPropertyDatetimeValue::class);
    }

    public function productBooleanProperties()
    {
        return $this->hasMany(ProductPropertyBooleanValue::class);
    }

    public function productIntegerProperties()
    {
        return $this->hasMany(ProductPropertyIntegerValue::class);
    }

    public function productTextProperties()
    {
        return $this->hasMany(ProductPropertyTextValue::class);
    }

    public function productDecimalProperties()
    {
        return $this->hasMany(ProductPropertyDecimalValue::class);
    }

    public function attribute()
    {
        return $this->belongsToMany(Attribute::class);
    }


    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function variations()
    {
        return $this->hasMany(ProductVariation::class);
    }


}
