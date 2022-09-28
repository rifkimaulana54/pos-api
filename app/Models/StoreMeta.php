<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoreMeta extends Model
{    
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'user_tm_store_metas';

    protected $searchable = [
        'store_id',
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
    public function store()
    {
        return $this->belongsTo('App\Models\Store');
    }
}
