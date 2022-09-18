<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductMeta extends Model
{    
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'product_tm_product_metas';

    protected $searchable = [
        'meta_key',
        'meta_value',
    ];
    public function getSearchable()
    {
        return $this->searchable;
    }

    /**
     * Get the user that owns the meta.
     */
    public function product()
    {
        return $this->belongsTo('App\Models\Product');
    }
}
