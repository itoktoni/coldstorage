<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Abbasudo\Purity\Traits\Filterable;
use Abbasudo\Purity\Traits\Sortable;
use App\Concerns\DefaultEntity;
use App\Concerns\OptionTrait;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Illuminate\Auth\Notifications\ResetPasswordNotification as BaseResetPasswordNotification;
use App\Notifications\ResetPasswordNotification;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;

/**
 * @mixin IdeHelperUser
 */
#[Fillable(['name', 'email', 'password', 'role', 'phone', 'user_agama', 'subscribe', 'affiliate_code', 'affiliate_reff', 'rekening_nama', 'rekening_bank', 'rekening_nomor', 'verified_at'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use DefaultEntity, Filterable, HasApiTokens, HasFactory, Notifiable, OptionTrait, Sortable, TwoFactorAuthenticatable;

    protected $table = 'users';

    protected $keyType = 'int';

    protected $primaryKey = 'id';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Columns available for filtering.
     */
    public static $filterColumns = [
        'name' => 'Name',
        'email' => 'Email',
        'phone' => 'Phone',
        'role' => 'Role',
    ];

    public static $sortColumns = [
        'name',
        'email',
        'phone',
        'role',
    ];

    /**
     * Validation rules.
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string',
            'email' => 'required|string',
            'role' => 'string',
            'password' => 'string',
        ];
    }

    public static function field_name(): string
    {
        return 'name';
    }

    public function isDeveloper(): bool
    {
        return $this->role === 'developer';
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isUser(): bool
    {
        return $this->role === 'user';
    }

    public function isEditor(): bool
    {
        return $this->role === 'editor';
    }

    public function komisi(): int
    {
        $earned = Affiliate::where('affiliate_id_user', $this->id)
            ->where('affiliate_status', '!=', 'rejected')
            ->sum('affiliate_jumlah');

        $cashout = Cashout::where('cashout_id_user', $this->id)
            ->whereIn('cashout_status', ['pending', 'completed'])
            ->selectRaw('SUM(cashout_jumlah + cashout_admin_fee) as total')
            ->value('total') ?? 0;

        return (int) $earned - (int) $cashout;
    }

    public function has_subscribe()
    {
        return $this->belongsTo(Subscribe::class, 'subscribe', 'subscribe_id');
    }

    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }
}
