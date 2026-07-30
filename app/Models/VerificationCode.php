<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VerificationCode extends Model
{
    public $timestamps = false;

    protected $table = 'verification_codes';

    protected $fillable = [
        'user_id',
        'code',
        'channel',
        'expires_at',
        'used',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used' => 'boolean',
    ];

    public function has_user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
