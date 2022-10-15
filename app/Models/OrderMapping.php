<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderMapping extends Model
{
   protected $table = 'order_tr_mapping_orders';

   protected $appends = ['status_label'];
   protected $searchable = [
      'order_qty',
      'subtotal',
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
         default:
            return 'Inactive';
            break;
      }
   }

   public function order()
   {
      return $this->belongsTo(Order::class);
   }

   public function product()
   {
      return $this->belongsTo(Product::class);
   }
}
