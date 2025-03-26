<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Jetstream\HasProfilePhoto;
use Illuminate\Notifications\Notifiable;
use Orion\Modules\Station\Models\Station;
use Orion\Modules\Building\Models\Building;
use Orion\Modules\Resource\Models\Resource;
use Orion\Modules\User\Models\UserAttribute;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Orion\Modules\Spacecraft\Models\Spacecraft;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use LaravelAndVueJS\Traits\LaravelPermissionToVueJS;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use HasProfilePhoto;
    use Notifiable;
    use TwoFactorAuthenticatable;
    use LaravelPermissionToVueJS;
    use HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'profile_photo_url',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the stations for the user.
     */
    public function stations()
    {
        return $this->hasMany(Station::class);
    }

    public function spacecrafts()
    {
        return $this->hasMany(Spacecraft::class);
    }

    public function buildings()
    {
        return $this->hasMany(Building::class);
    }

    public function resources()
    {
        return $this->belongsToMany(Resource::class, 'user_resources', 'user_id', 'resource_id')
            ->withPivot('amount');
    }

    public function attributes()
    {
        return $this->belongsToMany(UserAttribute::class, 'user_attributes', 'user_id', 'attribute_id');
    }
}
