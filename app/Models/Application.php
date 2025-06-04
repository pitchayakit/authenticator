<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OTPHP\TOTP;
use Carbon\Carbon;

class Application extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'secret',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected $hidden = [
        'secret',
    ];

    /**
     * Get the user that owns the OTP application.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Generate a new TOTP secret.
     */
    public static function generateSecret(): string
    {
        return TOTP::generate()->getSecret();
    }

    /**
     * Get the TOTP instance for this application.
     */
    public function getTotpInstance(): TOTP
    {
        $totp = TOTP::createFromSecret($this->secret);
        $totp->setLabel($this->user->email);
        $totp->setIssuer(config('app.name'));
        // Using defaults: SHA1, 6 digits, 30 seconds period
        
        return $totp;
    }



    /**
     * Verify an OTP code.
     */
    public function verifyOtp(string $otp): bool
    {
        $totp = $this->getTotpInstance();
        
        // Allow some time drift (previous and next windows)
        return $totp->verify($otp, null, 1);
    }



    /**
     * Scope for active applications.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
} 