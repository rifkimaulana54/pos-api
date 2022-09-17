<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyMeta extends Model
{    
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'user_tm_company_metas';

    protected $searchable = [
        'company_id',
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
    public function company()
    {
        return $this->belongsTo('App\Models\Company');
    }
}
