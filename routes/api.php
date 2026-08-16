<?php

use App\Http\Controllers\Api\CmsController;
use App\Http\Controllers\ApiNotificationPollController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PaymentWebhookController;
use App\Http\Controllers\UsersController;
use App\Services\CentrifugoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

Route::post('/webhook/payment', [PaymentWebhookController::class, 'handle'])->name('webhook.payment');

// CMS API Routes
Route::prefix('cms')->group(function () {
    Route::get('/content/{content}', [CmsController::class, 'show']);
    Route::get('/types/{type}/entries', [CmsController::class, 'indexByType']);
    Route::get('/types/{type}/blueprint', [CmsController::class, 'getBlueprintSchema']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/centrifugo/token', function (Request $request) {
        if (! config('langkahkecil.notification_enable')) {
            return response()->json(['token' => 'disabled']);
        }

        $centrifugo = app(CentrifugoService::class);
        $user = $request->user();

        if ($request->input('channel')) {
            return response()->json([
                'token' => $centrifugo->generateSubscriptionToken((string) $user->id, $request->input('channel')),
            ]);
        }

        return response()->json([
            'token' => $centrifugo->generateConnectionToken((string) $user->id),
        ]);
    });

    Route::auto('/users', UsersController::class, ['name' => 'users']);

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/send-verification', [AuthController::class, 'sendVerification'])->name('verification.send');
    Route::post('/verify', [AuthController::class, 'verify'])->name('verification.verify');

});

// Public polling endpoint (no auth required)
Route::get('/notifications/poll', [ApiNotificationPollController::class, 'poll']);
