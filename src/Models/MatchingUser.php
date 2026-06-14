<?php

namespace DevOps213\SSOauthenticated\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Device matching record (mirrors the SSO server's DeviceMatching model).
 *
 * @see \DevOps213\SSOauthenticated\Http\Controllers\LocalLoginController
 */
class MatchingUser extends Model
{
    protected $table = 'user_matching';

    protected $primaryKey = 'id';

    /**
     * The table only tracks a creation timestamp.
     */
    public const UPDATED_AT = 'created_at';

    protected $fillable = [
        'user_id',
        'user_agent',
        'platform',
        'device',
        'version',
        'matching_regex',
        'ip_request',
        'match_token',
        'confirmed_at',
    ];

    protected $casts = [
        'confirmed_at' => 'datetime',
    ];

    /**
     * Owning user (resolved from the application's auth provider).
     */
    public function user()
    {
        return $this->belongsTo(config('auth.providers.users.model', \App\Models\User::class), 'user_id');
    }
}
