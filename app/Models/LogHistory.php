<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LogHistory extends Model
{
   protected $table = 'tr_log_histories';

   // protected $appends = ['status_label'];
   protected $searchable = [
      'user_name',
      'ip_address',
      'action',
      'action_type',
      'service',
      'created_at',
      'updated_at',
   ];

   public function getSearchable()
   {
      return $this->searchable;
   }
}
