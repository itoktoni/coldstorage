<?php

namespace App\Http\Controllers;

use App\Models\Affiliate;
use App\Models\Cashout;
use App\Models\Discount;
use App\Models\Anak;
use App\Models\Plan;
use App\Models\Subscribe;
use App\Models\User;
use App\Models\VerificationCode;
use App\Services\Notification\NotificationChannelFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    private function userResponse(User $user): array
    {
        $user->load('has_subscribe.plan');
        $subscribe = $user->has_subscribe;

        $subscribeData = null;
        if ($subscribe) {
            $now = now();
            $endDate = $subscribe->subscribe_end_at ? \Carbon\Carbon::parse($subscribe->subscribe_end_at) : null;

            if ($endDate && $endDate->lt($now)) {
                $subscribeData = [
                    'subscribe_id' => $subscribe->subscribe_id,
                    'plan_id' => $subscribe->subscribe_id_plan,
                    'plan_nama' => $subscribe->plan?->plan_nama,
                    'plan_value' => $subscribe->subsribe_value,
                    'plan_harga' => $subscribe->subscribe_harga,
                    'subscribe_start_at' => $subscribe->subscribe_start_at ? \Carbon\Carbon::parse($subscribe->subscribe_start_at)->toIso8601String() : null,
                    'subscribe_end_at' => $subscribe->subscribe_end_at ? \Carbon\Carbon::parse($subscribe->subscribe_end_at)->toIso8601String() : null,
                    'subscribe_trial_at' => $subscribe->subscribe_trial_at ? \Carbon\Carbon::parse($subscribe->subscribe_trial_at)->toIso8601String() : null,
                    'expired' => true,
                ];
            } else {
                $subscribeData = [
                    'subscribe_id' => $subscribe->subscribe_id,
                    'plan_id' => $subscribe->subscribe_id_plan,
                    'plan_nama' => $subscribe->plan?->plan_nama,
                    'plan_value' => $subscribe->subsribe_value,
                    'plan_harga' => $subscribe->subscribe_harga,
                    'subscribe_start_at' => $subscribe->subscribe_start_at ? \Carbon\Carbon::parse($subscribe->subscribe_start_at)->toIso8601String() : null,
                    'subscribe_end_at' => $subscribe->subscribe_end_at ? \Carbon\Carbon::parse($subscribe->subscribe_end_at)->toIso8601String() : null,
                    'subscribe_trial_at' => $subscribe->subscribe_trial_at ? \Carbon\Carbon::parse($subscribe->subscribe_trial_at)->toIso8601String() : null,
                    'expired' => false,
                ];
            }
        }

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'user_agama' => $user->user_agama,
            'role' => $user->role,
            'affiliate_code' => $user->affiliate_code,
            'affiliate_reff' => $user->affiliate_reff,
            'affiliate_reff_nama' => $user->affiliate_reff ? User::where('affiliate_code', $user->affiliate_reff)->value('name') : null,
            'komisi' => $user->komisi(),
            'rekening_nama' => $user->rekening_nama,
            'rekening_bank' => $user->rekening_bank,
            'rekening_nomor' => $user->rekening_nomor,
            'subscribe' => $subscribeData,
        ];
    }

    private function plansData(): array
    {
        return Plan::where('plan_status', 1)
            ->orderBy('plan_id')
            ->get()
            ->map(function ($p) {
                $periodEnum = \App\PeriodEnum::tryFrom($p->plan_periode);
                return [
                    'id' => $p->plan_id,
                    'name' => $p->plan_nama,
                    'description' => $p->plan_keterangan,
                    'value' => $p->plan_value,
                    'price' => $p->plan_harga,
                    'price_strikethrough' => $p->plan_coret,
                    'fee' => $p->plan_fee,
                    'color' => $p->plan_color,
                    'recommended' => (bool) $p->plan_recomended,
                    'period' => $p->plan_periode,
                    'period_label' => $periodEnum?->description() ?? $p->plan_periode,
                    'interval' => $p->plan_interval,
                ];
            })
            ->toArray();
    }

    private function discountsData(): array
    {
        return Discount::where('discount_active', true)
            ->where(function ($q) {
                $q->whereNull('discount_start')->orWhere('discount_start', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('discount_end')->orWhere('discount_end', '>=', now());
            })
            ->get()
            ->map(fn ($d) => [
                'code' => $d->discount_code,
                'name' => $d->discount_nama,
                'type' => $d->discount_type,
                'value' => $d->discount_value,
                'min_transaction' => $d->discount_min_transaction,
                'max_amount' => $d->discount_max_amount,
            ])
            ->toArray();
    }

    private function appConfig(): array
    {
        return [
            'server_date' => now()->toIso8601String(),
            'trial_days' => (int) config('langkahkecil.trial_days', 10),
            'plans' => $this->plansData(),
            'discounts' => $this->discountsData(),
            'pilars' => $this->pilarsData(),
            'skills' => $this->skillsData(),
            'affiliate_config' => [
                'commission_rate' => (int) config('langkahkecil.affiliate.upgrade_commission_rate', 15),
            ],
        ];
    }

    private function pilarsData(): array
    {
        return \App\Models\Pilar::where('pilar_active', true)
            ->orderBy('pilar_sort_order')
            ->get()
            ->map(fn ($p) => $p->toArray())
            ->toArray();
    }

    private function skillsData(): array
    {
        return \App\Models\MasterSkill::where('skill_active', true)
            ->orderBy('skill_sort_order')
            ->get()
            ->map(fn ($s) => $s->toArray())
            ->toArray();
    }

    private function worksheetsData(): array
    {
        return \App\Models\MasterWorksheet::where('worksheet_active', true)
            ->orderBy('worksheet_sort_order')
            ->get()
            ->map(fn ($w) => $w->toArray())
            ->toArray();
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'The provided credentials are incorrect.',
            ], 421);
        }

        $token = $user->createToken('api_token')->plainTextToken;

        $needsVerification = !$this->isVerificationBypassed() && !$user->verified_at;

        if ($needsVerification) {
            $backendVerification = config('langkahkecil.verification.register_backend', false);
            $gateway = config('langkahkecil.verification.gateway', 'whatsapp');

            if ($backendVerification) {
                $this->dispatchVerificationCode($user, $gateway);
            }

            return response()->json([
                'access_token' => $token,
                'token_type' => 'Bearer',
                'needs_verification' => true,
                'verification_gateway' => $gateway,
                'message' => 'Akun belum terverifikasi. Silakan verifikasi terlebih dahulu.',
                'user' => $this->userResponse($user),
            ]);
        }

        $anakList = Anak::where('anak_id_user', $user->id)
            ->with([
                'has_skills.has_activities',
                'has_completed_skills',
                'has_challenges',
                'has_challenge_histories',
                'has_checklists',
                'has_schedules',
                'has_worksheets',
                'has_evaluations',
            ])
            ->get();

        return response()->json(array_merge([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $this->userResponse($user),
            'anak_list' => $anakList,
        ], $this->appConfig()));
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone' => 'nullable|string|max:20|unique:users,phone',
            'password' => 'required|string|min:6|confirmed',
            'ref' => 'nullable|string|max:30',
            'trial' => 'nullable|integer|min:1',
        ], [
            'name.required' => 'Nama wajib diisi',
            'email.required' => 'Email wajib diisi',
            'email.email' => 'Format email tidak valid',
            'email.unique' => 'Email sudah digunakan',
            'password.required' => 'Password wajib diisi',
            'password.unique' => 'No Telp ini sudah digunakan',
            'password.min' => 'Password minimal 6 karakter',
            'password.confirmed' => 'Konfirmasi password tidak cocok',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $affiliateReff = null;
        if ($request->input('ref')) {
            $referrer = User::where('affiliate_code', $request->input('ref'))->first();
            if ($referrer) {
                $affiliateReff = $request->input('ref');
            }
        }

        $affiliateCode = strtoupper(substr(md5(uniqid($request->email, true)), 0, 8));

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => $request->password,
            'role' => 'trial',
            'affiliate_code' => $affiliateCode,
            'affiliate_reff' => $affiliateReff,
        ]);

        $plan = Plan::where('plan_status', 1)->first();

        if ($plan && $request->exists('trial')) {
            $trialDays = (int) $request->input('trial');
            $subscription = Subscribe::create([
                'subscribe_id_user' => $user->id,
                'subscribe_harga' => $plan->plan_harga,
                'subscribe_discount' => $plan->plan_harga,
                'subscribe_total' => 0,
                'subscribe_id_plan' => $plan->plan_id,
                'subsribe_value' => $plan->plan_value ?? 1,
                'subscribe_trial_at' => now(),
                'subscribe_start_at' => now(),
                'subscribe_end_at' => now()->addDays($trialDays),
                'subscribe_created_at' => now(),
            ]);
            $user->update(['subscribe' => $subscription->subscribe_id]);
        }

        if ($affiliateReff) {
            $referrer = User::where('affiliate_code', $affiliateReff)->first();
            if ($referrer) {
                $registerBonus = (int) config('langkahkecil.affiliate.register_bonus', 500);
                Affiliate::create([
                    'affiliate_id_user' => $referrer->id,
                    'affiliate_id_from_user' => $user->id,
                    'affiliate_tipe' => 'register',
                    'affiliate_jumlah' => $registerBonus,
                    'affiliate_catatan' => "Bonus referral: " . $user->name . " bergabung",
                    'affiliate_status' => 'pending',
                    'affiliate_created_at' => now(),
                    'affiliate_updated_at' => now(),
                ]);
            }
        }

        $token = $user->createToken('api_token')->plainTextToken;

        $backendVerification = config('langkahkecil.verification.register_backend', false);
        $gateway = config('langkahkecil.verification.gateway', 'whatsapp');

        if ($backendVerification) {
            $this->dispatchVerificationCode($user, $gateway);
        }

        $needsVerification = !$this->isVerificationBypassed();

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'needs_verification' => $needsVerification,
            'verification_gateway' => $needsVerification ? $gateway : null,
            'message' => $needsVerification ? 'Akun berhasil dibuat. Silakan verifikasi.' : 'Akun berhasil dibuat.',
            'user' => $this->userResponse($user),
        ], 201);
    }

    public function sendVerification(Request $request)
    {
        $request->validate([
            'channel' => 'required|in:whatsapp,telegram,email,log',
        ]);

        $user = $request->user();
        if ($user->verified_at) {
            return response()->json(['message' => 'Sudah terverifikasi']);
        }

        $lastCode = VerificationCode::where('user_id', $user->id)->latest()->first();
        if ($lastCode && $lastCode->created_at && $lastCode->created_at->diffInSeconds(now()) < 60) {
            $waitSeconds = 60 - $lastCode->created_at->diffInSeconds(now());
            return response()->json([
                'message' => "Tunggu {$waitSeconds} detik sebelum mengirim ulang kode.",
                'cooldown' => $waitSeconds,
            ], 429);
        }

        $sent = $this->dispatchVerificationCode($user, $request->channel);

        if (!$sent) {
            return response()->json([
                'message' => "Gagal mengirim kode via {$request->channel}. Pastikan konfigurasi sudah benar.",
            ], 500);
        }

        return response()->json(['message' => "Kode verifikasi telah dikirim via {$request->channel}"]);
    }

    private function dispatchVerificationCode(User $user, string $channel): bool
    {
        $codeLength = (int) config('langkahkecil.verification.code_length', 6);
        $expiresMinutes = (int) config('langkahkecil.verification.expires_minutes', 10);

        $code = str_pad(random_int(0, pow(10, $codeLength) - 1), $codeLength, '0', STR_PAD_LEFT);

        $data = [
            'user_id' => $user->id,
            'code' => $code,
            'channel' => $channel,
            'expires_at' => now()->addMinutes($expiresMinutes),
        ];

        VerificationCode::create($data);

        Log::info("[VERIFICATION] Code sent to user_id={$user->id} email={$user->email} channel={$channel} code={$code} expires_at={$data['expires_at']}");

        $appName = config('app.name', 'Jejak Tumbuh');
        $to = $channel === 'email' ? $user->email : ($user->phone ?? $user->email);

        if ($channel === 'email') {
            $message = "Halo {$user->name},\n\n";
            $message .= "Terima kasih telah mendaftar di {$appName}!\n\n";
            $message .= "Berikut adalah kode verifikasi Anda:\n\n";
            $message .= "{$code}\n\n";
            $message .= "Kode ini berlaku selama {$expiresMinutes} menit.\n";
            $message .= "Jika Anda tidak merasa mendaftar, abaikan pesan ini.\n\n";
            $message .= "Salam hangat,\nTim {$appName}";
        } else {
            $message = "Halo {$user->name}!\n\n";
            $message .= "Terima kasih telah mendaftar di {$appName}.\n\n";
            $message .= "Kode verifikasi Anda: {$code}\n";
            $message .= "Berlaku selama {$expiresMinutes} menit.\n\n";
            $message .= "Jangan bagikan kode ini kepada siapa pun.\n\n";
            $message .= "Salam hangat,\n{$appName}";
        }

        return NotificationChannelFactory::make($channel)->send($to, $message);
    }

    public function verify(Request $request)
    {
        $request->validate([
            'code' => 'required|string|size:6',
        ]);

        $user = $request->user();
        if ($user->verified_at) {
            return response()->json(['message' => 'Sudah terverifikasi', 'verified' => true]);
        }

        $record = VerificationCode::where('user_id', $user->id)
            ->where('code', $request->code)
            ->where('used', false)
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if (!$record) {
            return response()->json(['message' => 'Kode tidak valid atau sudah kedaluwarsa'], 422);
        }

        $record->update(['used' => true]);
        $user->update(['verified_at' => now()]);

        return response()->json(array_merge([
            'message' => 'Verifikasi berhasil!',
            'verified' => true,
            'user' => $this->userResponse($user),
        ], $this->appConfig()));
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out']);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255|unique:users,email,'.$user->id,
            'phone' => 'required|string|max:20|unique:users,phone,'.$user->id,
            'user_agama' => 'sometimes|nullable|in:islam,kristen_protestan,kristen_katolik,hindu,buddha,konghucu',
        ]);

        if ($request->has('name')) {
            $user->name = $request->name;
        }
        if ($request->has('email')) {
            $user->email = $request->email;
            $user->email_verified_at = null;
        }
        $user->phone = $request->phone;
        if ($request->has('user_agama')) {
            $user->user_agama = $request->user_agama;
        }

        $user->save();

        return response()->json([
            'user' => $this->userResponse($user),
        ]);
    }

    public function me(Request $request)
    {
        $user = $request->user();

        $anakList = Anak::where('anak_id_user', $user->id)
            ->with([
                'has_skills.has_activities',
                'has_completed_skills',
                'has_challenges',
                'has_challenge_histories',
                'has_checklists',
                'has_schedules',
                'has_worksheets',
                'has_schedule_histories',
                'has_evaluations',
            ])
            ->get();

        $response = array_merge([
            'user' => $this->userResponse($user),
            'anak_list' => $anakList,
        ], $this->appConfig());

        // Add activities grouped for offline support
        $response['activities_grouped'] = $this->activitiesGroupedData();

        if (!$this->isVerificationBypassed() && !$user->verified_at) {
            $response['needs_verification'] = true;
            $response['verification_gateway'] = config('langkahkecil.verification.gateway', 'whatsapp');
        }

        return response()->json($response);
    }

    private function activitiesGroupedData(): array
    {
        $activities = \App\Models\Activity::where('active', true)
            ->where('status', 'approved')
            ->orderBy('sort_order')
            ->get()
            ->groupBy('type')
            ->map(fn ($items) => $items->toArray())
            ->toArray();

        return $activities;
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $user = $request->user();

        if (! Hash::check($request->current_password, $user->password)) {
            return response()->json(['message' => 'Password lama salah'], 422);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        return response()->json(['message' => 'Password berhasil diubah']);
    }

    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => ['nullable', 'email'],
            'phone' => ['nullable', 'string'],
        ]);

        $channel = config('langkahkecil.verification.forgot_gateway', 'email');
        $user = null;

        if ($request->filled('phone')) {
            $user = User::where('phone', $request->phone)->first();
        } elseif ($request->filled('email')) {
            $user = User::where('email', $request->email)->first();
        }

        if (!$user) {
            return response()->json(['message' => 'Akun tidak ditemukan.'], 422);
        }

        $token = Password::broker()->createToken($user);
        $frontendUrl = config('langkahkecil.frontend_url', config('app.url'));
        $resetUrl = "{$frontendUrl}/reset-password?token={$token}&email=" . urlencode($user->email);

        $appName = config('app.name', 'Jejak Tumbuh');
        $message = "Halo {$user->name}!\n\n";
        $message .= "Kami menerima permintaan untuk mereset password akun {$appName} Anda.\n\n";
        $message .= "Klik link berikut untuk reset password:\n{$resetUrl}\n\n";
        $message .= "Link ini berlaku selama 60 menit.\n";
        $message .= "Jika Anda tidak meminta reset password, abaikan pesan ini.\n\n";
        $message .= "Salam hangat,\n{$appName}";

        $to = $channel === 'email' ? $user->email : ($user->phone ?? $user->email);
        $sent = NotificationChannelFactory::make($channel)->send($to, $message);

        if ($sent) {
            $label = $channel === 'email' ? 'email' : ($channel === 'whatsapp' ? 'WhatsApp' : 'Telegram');
            return response()->json(['message' => "Link reset password telah dikirim ke {$label} Anda."]);
        }

        return response()->json(['message' => 'Gagal mengirim link reset. Silakan coba lagi atau hubungi admin.'], 500);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => $password,
                ])->save();
            }
        );

        if ($status == Password::PASSWORD_RESET) {
            return response()->json(['message' => 'Password berhasil diubah.']);
        }

        return response()->json(['message' => 'Token tidak valid atau sudah kedaluwarsa.'], 422);
    }

    public function updateAffiliateCode(Request $request)
    {
        $request->validate([
            'affiliate_code' => 'required|string|min:4|max:20|alpha_dash',
        ]);

        $user = $request->user();
        $code = strtoupper($request->affiliate_code);

        $exists = User::where('affiliate_code', $code)->where('id', '!=', $user->id)->exists();
        if ($exists) {
            return response()->json(['message' => 'Kode sudah digunakan orang lain'], 422);
        }

        $user->update(['affiliate_code' => $code]);

        return response()->json([
            'user' => $this->userResponse($user),
        ]);
    }

    public function referralList(Request $request)
    {
        $user = $request->user();

        $referrals = collect();
        if (!empty($user->affiliate_code)) {
            $referrals = User::where('affiliate_reff', $user->affiliate_code)
                ->where('id', '!=', $user->id)
                ->select('id', 'name', 'email', 'role', 'created_at')
                ->orderByDesc('created_at')
                ->limit(50)
                ->get()
                ->map(fn ($u) => [
                    'id' => $u->id,
                    'name' => $u->name,
                    'email' => $this->maskEmail($u->email),
                    'role' => $u->role,
                    'joined_at' => $u->created_at->toIso8601String(),
                ]);
        }

        $earnings = Affiliate::where('affiliate_id_user', $user->id)->get();

        $totalEarning = $earnings->sum('affiliate_jumlah');
        $totalRegister = $earnings->where('affiliate_tipe', 'register')->sum('affiliate_jumlah');
        $totalUpgrade = $earnings->where('affiliate_tipe', 'upgrade')->sum('affiliate_jumlah');
        $pendingEarning = $earnings->where('affiliate_status', 'pending')->sum('affiliate_jumlah');

        return response()->json([
            'total' => $referrals->count(),
            'referrals' => $referrals,
            'komisi' => $user->komisi(),
            'earnings' => [
                'total' => $totalEarning,
                'register' => $totalRegister,
                'upgrade' => $totalUpgrade,
                'pending' => $pendingEarning,
            ],
            'rates' => [
                'register_bonus' => (int) config('langkahkecil.affiliate.register_bonus', 500),
                'commission_rate' => (int) config('langkahkecil.affiliate.upgrade_commission_rate', 15),
            ],
            'cashout' => [
                'minimum' => (int) config('langkahkecil.cashout.minimum', 50000),
                'admin_rate' => (int) config('langkahkecil.cashout.admin_rate', 3),
            ],
            'banks' => config('langkahkecil.banks', []),
        ]);
    }

    public function updateRekening(Request $request)
    {
        $request->validate([
            'rekening_nama' => 'required|string|max:100',
            'rekening_bank' => 'required|string|max:50',
            'rekening_nomor' => 'required|string|max:30',
        ]);

        $user = $request->user();
        $data = $request->only(['rekening_nama', 'rekening_bank', 'rekening_nomor']);

        $user->update($data);

        return response()->json([
            'user' => $this->userResponse($user),
        ]);
    }

    public function requestCashout(Request $request)
    {
        $minimum = (int) config('langkahkecil.cashout.minimum', 20000);
        $adminRate = (int) config('langkahkecil.cashout.admin_rate', 3);

        $request->validate([
            'amount' => "required|integer|min:{$minimum}",
        ]);

        $user = $request->user();
        $adminFee = (int) round($request->amount * $adminRate / 100);
        $totalDeduct = $request->amount + $adminFee;

        if ($user->komisi() < $totalDeduct) {
            return response()->json(['message' => 'Saldo komisi tidak mencukupi (termasuk platform fee)'], 422);
        }

        if (!$user->rekening_nama || !$user->rekening_bank || !$user->rekening_nomor) {
            return response()->json(['message' => 'Lengkapi data rekening terlebih dahulu'], 422);
        }

        $received = $request->amount;

        $cashout = Cashout::create([
            'cashout_id_user' => $user->id,
            'cashout_jumlah' => $request->amount,
            'cashout_admin_fee' => $adminFee,
            'cashout_diterima' => $received,
            'cashout_rekening_bank' => $user->rekening_bank,
            'cashout_rekening_nomor' => $user->rekening_nomor,
            'cashout_rekening_nama' => $user->rekening_nama,
            'cashout_status' => 'pending',
            'cashout_created_at' => now(),
            'cashout_updated_at' => now(),
        ]);

        return response()->json([
            'message' => 'Permintaan pencairan berhasil diajukan. Maksimal 1 hari kerja.',
            'komisi' => $user->komisi(),
            'cashout' => $cashout,
        ]);
    }

    public function cashoutList(Request $request)
    {
        $cashouts = Cashout::where('cashout_id_user', $request->user()->id)
            ->orderByDesc('cashout_created_at')
            ->limit(20)
            ->get();

        return response()->json(['cashouts' => $cashouts]);
    }

    private function isVerificationBypassed(): bool
    {
        return (bool) config('langkahkecil.bypass.email_verification.api', false);
    }

    private function maskEmail(string $email): string
    {
        $parts = explode('@', $email);
        if (count($parts) !== 2) return $email;

        $local = $parts[0];
        $domain = $parts[1];

        $localLen = strlen($local);
        if ($localLen <= 2) {
            $maskedLocal = $local . '***';
        } else {
            $maskedLocal = substr($local, 0, 2) . str_repeat('*', min($localLen - 2, 5));
        }

        return $maskedLocal . '@' . $domain;
    }
}
