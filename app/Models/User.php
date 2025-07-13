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
        'profile_image',
        'date_of_birth',
        'address',
        'city',
        'state',
        'postal_code',
        'country',
        'status',
        'rewards_balance',
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
            'rewards_balance' => 'decimal:2',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the profile image URL.
     */
    public function getProfileImageAttribute($value)
    {
        if (!$value) {
            return null;
        }
        
        return Storage::disk('public')->url($value);
    }

    /**
     * Get the user's orders.
     */
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Get the user's cart items.
     */
    public function cart()
    {
        return $this->hasMany(Cart::class);
    }

    /**
     * Get the user's reviews.
     */
    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    /**
     * Check if user has sufficient rewards balance.
     */
    public function hasRewardsBalance($amount)
    {
        return $this->rewards_balance >= $amount;
    }

    /**
     * Use rewards balance.
     */
    public function useRewardsBalance($amount)
    {
        if (!$this->hasRewardsBalance($amount)) {
            throw new \Exception('Insufficient rewards balance');
        }

        $this->decrement('rewards_balance', $amount);
        return $this;
    }

    /**
     * Add rewards balance.
     */
    public function addRewardsBalance($amount)
    {
        $this->increment('rewards_balance', $amount);
        return $this;
    }

}
