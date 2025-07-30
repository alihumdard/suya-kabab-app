<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'email_verified_at',
        'password',
        'phone',
        'gender',
        'date_of_birth',
        'address',
        'city',
        'state',
        'postal_code',
        'country',
        'status',
        'last_login_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
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
            'last_login_at' => 'datetime',
            'date_of_birth' => 'date',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the user's images.
     */
    public function images()
    {
        return $this->morphMany(Image::class, 'imageable')->active();
    }

    /**
     * Get the profile image URL.
     */
    public function getProfileImageAttribute()
    {
        // Use loaded relationship if available, otherwise query
        if ($this->relationLoaded('images')) {
            return $this->images->first()?->url;
        }

        return $this->images()->first()?->url;
    }

    /**
     * Get the user's orders.
     */
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Get the user's reviews.
     */
    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    /**
     * Get the user's favorite products.
     */
    public function favorites()
    {
        return $this->belongsToMany(Product::class, 'user_favorites')->withTimestamps();
    }

    /**
     * Use rewards balance.
     */
    public function useRewardsBalance($amount)
    {
        throw new \Exception('Rewards balance functionality is removed');
    }

    /**
     * Add rewards balance.
     */
    public function addRewardsBalance($amount)
    {
        throw new \Exception('Rewards balance functionality is removed');
    }
}
