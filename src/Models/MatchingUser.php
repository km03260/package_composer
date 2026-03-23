<?php

namespace DevOps213\SSOauthenticated\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class MatchingUser extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'user_matching';

    protected $primaryKey = 'id';

}
