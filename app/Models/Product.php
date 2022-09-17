<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $table = 'product_tm_products';

    protected $appends = ['status_label'];
    protected $searchable = [
        'id',
        'product_name',
        'product_description',
        'product_price'
    ];

    public function getSearchable()
    {
        return $this->searchable;
    }

    public function permissions()
    {
        return $this->belongsToMany(Permission::class);
    }

    public function getStatusLabelAttribute()
    {
        switch ($this->status) 
        {
            case 0:
                return 'Archived';
                break;
            case 2:
                return 'Inactive';
                break;
            default:
                return 'Active';
                break;
        }
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function company()
    {
        return $this->belongsTo('App\Models\Company');
    }
}
