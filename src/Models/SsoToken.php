<?php

namespace DevOps213\SSOauthenticated\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Models\User;

class SsoToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'module_id',
        'access_token',
        'refresh_token',
        'expires_at',
        'refresh_expires_at',
        'scopes'
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'refresh_expires_at' => 'datetime'
    ];

    // Accessor for scopes
    public function getScopesAttribute($value)
    {
        return json_decode($value, true) ?? [];
    }

    // Mutator for scopes
    public function setScopesAttribute($value)
    {
        $this->attributes['scopes'] = json_encode($value ?? []);
    }

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function module()
    {
        return $this->belongsTo(SsoModule::class, 'module_id');
    }

    // Check if access token is expired
    public function isAccessTokenExpired()
    {
        return $this->expires_at->isPast();
    }

    // Check if refresh token is expired
    public function isRefreshTokenExpired()
    {
        return $this->refresh_expires_at && $this->refresh_expires_at->isPast();
    }

    // Generate new tokens
    public static function generate($userId, $moduleId, $scopes = [], $accessTokenLifetime = 2, $refreshTokenLifetime = 30)
    {
        // Clean up expired tokens for this user/module
        self::where('user_id', $userId)
            ->where('module_id', $moduleId)
            ->where('expires_at', '<', now())
            ->delete();

        // Generate tokens
        $accessToken = Str::random(60);
        $refreshToken = Str::random(60);

        $token = new self();
        $token->user_id = $userId;
        $token->module_id = $moduleId;
        $token->access_token = hash('sha256', $accessToken);
        $token->refresh_token = hash('sha256', $refreshToken);
        $token->expires_at = now()->addHours($accessTokenLifetime);
        $token->refresh_expires_at = now()->addDays($refreshTokenLifetime);
        $token->scopes = $scopes;
        $token->save();

        // Add plain tokens for response
        $token->plain_access_token = $accessToken;
        $token->plain_refresh_token = $refreshToken;

        return $token;
    }

    // Refresh access token
    public function refreshAccessToken()
    {
        if ($this->isRefreshTokenExpired()) {
            return null;
        }

        $newAccessToken = Str::random(60);
        $this->access_token = hash('sha256', $newAccessToken);
        $this->expires_at = now()->addHours(2);
        $this->save();

        $this->plain_access_token = $newAccessToken;

        return $this;
    }

    // Verify access token
    public static function verify($token, $moduleId = null)
    {
        $hashedToken = hash('sha256', $token);

        $query = self::where('access_token', $hashedToken)
            ->where('expires_at', '>', now())
            ->with(['user', 'module']);

        if ($moduleId) {
            $query->where('module_id', $moduleId);
        }

        return $query->first();
    }

    // Verify refresh token
    public static function verifyRefreshToken($refreshToken, $moduleId = null)
    {
        $hashedToken = hash('sha256', $refreshToken);

        $query = self::where('refresh_token', $hashedToken)
            ->where('refresh_expires_at', '>', now())
            ->with(['user', 'module']);

        if ($moduleId) {
            $query->where('module_id', $moduleId);
        }

        return $query->first();
    }

    // Get remaining time in seconds
    public function getRemainingTime()
    {
        return max(0, now()->diffInSeconds($this->expires_at, false));
    }

    // Check if token should be refreshed (within last 5 minutes)
    public function shouldRefresh()
    {
        return $this->getRemainingTime() < 300; // 5 minutes
    }

    public function scopeGetTokenRelatedUser($query, $token)
    {
        $hashedToken = hash('sha256', $token);

        return SsoToken::where('access_token', $hashedToken)
            ->where('expires_at', '>', now())
            ->with('user')
            ->first();
    }
}
