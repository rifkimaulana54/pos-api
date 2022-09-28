<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Store extends Model
{
   protected $table = 'user_tm_stores';

   protected $appends = ['status_label'];
   protected $searchable = [
      'name',
      'store_address',
      'no_telepone',
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
      return $this->hasMany('App\Models\StoreMeta');
   }
}
