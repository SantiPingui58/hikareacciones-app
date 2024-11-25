<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TwitchUser extends Model
{
    use HasFactory;

    protected $fillable = [
        'twitch_id',
        'display_name',
        'profile_image_url',
        'email',
        'subscription_end',
        'access_token', 
          'refresh_token',
        'token_expiration', 
    ];
}
