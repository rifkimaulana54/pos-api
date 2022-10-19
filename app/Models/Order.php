<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
   protected $table = 'order_tm_orders';

   protected $appends = ['status_label'];
   protected $searchable = [
      'order_code',
      'customer_name',
      'total_order',
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
         case 2:
            return 'Waiting List';
            break;
         case 3:
            return 'In Progress';
            break;
         case 4:
            return 'Completed';
            break;
            break;
         default:
            return 'Inactive';
            break;
      }
   }

   public function mapping()
   {
      return $this->hasMany('App\Models\OrderMapping');
   }

   public function store()
   {
      return $this->belongsTo(Store::class);
   }
}
