<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class EmailOtp extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'email',
        'code_hash',
        'type',
        'expires_at',
        'attempts',
        'sent_at',
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
            'sent_at' => 'datetime',
        ];
    }

    /**
     * Scope to get active (non-expired) OTPs.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('expires_at', '>', Carbon::now());
    }

    /**
     * Scope to get OTPs by email and type.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $email
     * @param string $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForEmailAndType($query, string $email, string $type)
    {
        return $query->where('email', $email)->where('type', $type);
    }

    /**
     * Check if OTP is expired.
     *
     * @return bool
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Check if OTP is locked due to max attempts.
     *
     * @return bool
     */
    public function isLocked(): bool
    {
        return $this->attempts >= 5;
    }
}
