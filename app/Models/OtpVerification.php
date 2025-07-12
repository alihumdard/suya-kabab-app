<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class OtpVerification extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'email',
        'otp',
        'type',
        'expires_at',
        'used_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
        ];
    }

    /**
     * Check if OTP is expired.
     */
    public function isExpired()
    {
        return Carbon::now()->greaterThan($this->expires_at);
    }

    /**
     * Check if OTP is used.
     */
    public function isUsed()
    {
        return !is_null($this->used_at);
    }

    /**
     * Check if OTP is valid (not expired and not used).
     */
    public function isValid()
    {
        return !$this->isExpired() && !$this->isUsed();
    }
}