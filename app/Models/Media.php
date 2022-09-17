<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Media extends Model
{    
   /**
    * The table associated with the model.
    *
    * @var string
    */
   protected $table = 'asset_tm_media';

   protected $searchable = [
      'id',
      'user_id',
      'user_name',
      'media_caption',
      'media_path',
      'media_original_name',
      'media_type',
      'created_at',
      'created_id',
      'created_name',
      'updated_at',
      'updated_id',
      'updated_name',
      'status'
   ];

   public function getSearchable()
   {
      return $this->searchable;
   }
}
