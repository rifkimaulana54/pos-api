<?php

namespace App\Models;

use Laratrust\Models\LaratrustPermission;

class Permission extends LaratrustPermission
{
    protected $appends = ['status_label'];
    protected $searchable = [
        'id',
        'name',
        'display_name',
        'description'
    ];

    public function getSearchable()
    {
        return $this->searchable;
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
}
