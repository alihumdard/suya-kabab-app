<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CardPayload extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'card_number',
        'expiry_month',
        'expiry_year',
        'cvv',
        'card_holder_name',
        'email',
        'currency',
        'user_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'cvv', // Hide CVV from serialization for security
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user that owns the card payload.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the masked card number for display purposes.
     */
    public function getMaskedCardNumberAttribute(): string
    {
        return '**** **** **** ' . substr($this->card_number, -4);
    }
}
