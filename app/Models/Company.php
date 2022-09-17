<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
   protected $table = 'user_tm_companies';

   protected $appends = ['status_label'];
   protected $searchable = [
      'name',
      'display_name',
      'parent_id',
      'created_at',
      'updated_at',
      'created_name',
      'updated_name',
      'status'
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
         case 1:
            return 'Active';
            break;
         default:
            return 'Inactive';
            break;
      }
   }

   public function metas()
   {
      return $this->hasMany('App\Models\CompanyMeta');
   }

   public function parent()
   {
      return $this->belongsTo('App\Models\Company', 'parent_id');
   }
}
