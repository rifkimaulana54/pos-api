<?php

namespace App\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Lumen\Auth\Authorizable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Notifications\Notifiable;
use Laratrust\Traits\LaratrustUserTrait;
use phpDocumentor\Reflection\Location;

class User extends Model implements
    AuthenticatableContract,
    // AuthorizableContract
    JWTSubject
{
    use LaratrustUserTrait;
    use Authenticatable,
        // Authorizable, 
        HasFactory;

    protected $table = 'user_tm_users';
    protected $appends = ['status_label'];
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'fullname', 'email',
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'password',
    ];

    protected $searchable = [
        'fullname',
        'email',
        'phone',
        'created_at',
        'updated_at',
    ];

    public function getSearchable()
    {
        return $this->searchable;
    }

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        $return = [
            'nm'    => $this->fullname,
            'acl'   => array_column(json_decode($this->allPermissions()), 'name'),
            'rl'    => array_column(json_decode($this->roles), 'name'),
        ];

        if (!empty($this->store_id))
            $return['st'] = $this->store_id;
        if (!empty($this->email))
            $return['ue'] = $this->email;

        return $return;
    }

    // public function locations()
    // {
    //     return $this->belongsToMany('App\Models\Location', 'user_tm_user_location');
    // }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function metas()
    {
        return $this->hasMany(UserMeta::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function getStatusLabelAttribute()
    {
        switch ($this->status) {
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
}
