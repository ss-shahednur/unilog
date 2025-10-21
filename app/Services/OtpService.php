<?php

namespace App\Services;

use App\Models\EmailOtp;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Mail\Auth\VerificationOtpMail;
use App\Mail\Auth\ResetPasswordOtpMail;

class OtpService
{
    /**
     * OTP expiration time in minutes.
     */
    private const OTP_EXPIRATION_MINUTES = 10;

    /**
     * Maximum OTP verification attempts.
     */
    private const MAX_ATTEMPTS = 5;

    /**
     * Minimum seconds between resends.
     */
    private const RESEND_THROTTLE_SECONDS = 60; // 1 minute

    /**
     * Maximum resends per hour.
     */
    private const MAX_RESENDS_PER_HOUR = 5;

    /**
     * Generate and send a new OTP.
     *
     * @param string $email
     * @param string $type ('verify' or 'reset')
     * @return string The plain OTP (for sending via email)
     * @throws \Exception
     */
    public function generate(string $email, string $type): string
    {
        // Check throttling
        $this->enforceResendThrottle($email, $type);

        // Invalidate any existing active OTPs for this email+type
        $this->invalidateAll($email, $type);

        // Generate 6-digit OTP
        $otp = str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);

        // Store hashed OTP
        EmailOtp::create([
            'email' => $email,
            'code_hash' => Hash::make($otp),
            'type' => $type,
            'expires_at' => Carbon::now()->addMinutes(self::OTP_EXPIRATION_MINUTES),
            'attempts' => 0,
            'sent_at' => Carbon::now(),
        ]);

        // Send email based on type
        $this->sendOtpEmail($email, $otp, $type);

        return $otp;
    }

    /**
     * Verify an OTP code.
     *
     * @param string $email
     * @param string $type
     * @param string $otp
     * @return bool
     */
    public function verify(string $email, string $type, string $otp): bool
    {
        $record = EmailOtp::forEmailAndType($email, $type)
            ->active()
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$record) {
            return false; // No active OTP found
        }

        // Check if locked due to max attempts
        if ($record->isLocked()) {
            return false;
        }

        // Increment attempts
        $record->increment('attempts');

        // Verify OTP
        if (!Hash::check($otp, $record->code_hash)) {
            return false;
        }    // OTP is valid - invalidate it (one-time use)
            $record->delete();    return true;
        }

    /**
     * Invalidate all active OTPs for a given email and type.
     *
     * @param string $email
     * @param string $type
     * @return void
     */
    public function invalidateAll(string $email, string $type): void
    {
        EmailOtp::forEmailAndType($email, $type)
            ->active()
            ->delete();
    }

    /**
     * Get remaining seconds before user can resend OTP.
     *
     * @param string $email
     * @param string $type
     * @return int Seconds to wait (0 if can resend now)
     */
    public function remainingWaitForResend(string $email, string $type): int
    {
        $lastSent = EmailOtp::forEmailAndType($email, $type)
            ->orderBy('sent_at', 'desc')
            ->first();

        if (!$lastSent || !$lastSent->sent_at) {
            return 0;
        }

        $elapsed = Carbon::now()->diffInSeconds($lastSent->sent_at, false);
        $remaining = self::RESEND_THROTTLE_SECONDS - $elapsed;

        return max(0, (int) $remaining);
    }

    /**
     * Enforce resend throttling (1/min, 5/hour).
     *
     * @param string $email
     * @param string $type
     * @return void
     * @throws \Exception
     */
    private function enforceResendThrottle(string $email, string $type): void
    {
        // Check 1-minute throttle
        $wait = $this->remainingWaitForResend($email, $type);
        if ($wait > 0) {
            throw new \Exception("Please wait {$wait} seconds before requesting another OTP.");
        }

        // Check 5 per hour limit
        $recentSends = EmailOtp::forEmailAndType($email, $type)
            ->where('sent_at', '>', Carbon::now()->subHour())
            ->count();

        if ($recentSends >= self::MAX_RESENDS_PER_HOUR) {
            throw new \Exception('Too many OTP requests. Please try again later.');
        }
    }

    /**
     * Send OTP via email based on type.
     *
     * @param string $email
     * @param string $otp
     * @param string $type
     * @return void
     */
    private function sendOtpEmail(string $email, string $otp, string $type): void
    {
        if ($type === 'verify') {
            Mail::to($email)->send(new VerificationOtpMail($otp));
        } elseif ($type === 'reset') {
            Mail::to($email)->send(new ResetPasswordOtpMail($otp));
        }
    }
}