<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserMeta extends Model
{
   protected $table = 'user_tm_user_metas';
   protected $searchable = [
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
    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }
}
