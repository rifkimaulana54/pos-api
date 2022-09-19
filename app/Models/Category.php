<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $table = 'product_tm_categories';

    protected $appends = ['status_label'];
    protected $searchable = [
        'id',
        'category_name',
        'category_description',
        'created_at',
        'updated_at',
        'status'
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

    public function products()
    {
        return $this->hasMany('App\Models\Product');
    }

    public function company()
    {
        return $this->belongsTo('App\Models\Company');
    }

    public function parent()
    {
        return $this->belongsTo('App\Models\Category', 'parent_id');
    }
}
