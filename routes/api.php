<?php

use App\Actions\PlanAction;
use App\Http\Controllers\ActivityController;
use App\Http\Controllers\AddonController;
use App\Http\Controllers\AnakController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChallengeController;
use App\Http\Controllers\ChallengeHistoryController;
use App\Http\Controllers\ChecklistController;
use App\Http\Controllers\CompletedSkillController;
use App\Http\Controllers\Api\CmsController;
use App\Http\Controllers\ContentApprovalController;
use App\Http\Controllers\DiscountController;
use App\Http\Controllers\EvaluationController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PaymentMethodController;
use App\Http\Controllers\PaymentWebhookController;
use App\Http\Controllers\PilarController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\SkillActivityController;
use App\Http\Controllers\SkillController;
use App\Http\Controllers\WorksheetController;
use App\Models\Activity;
use App\Models\Plan;
use App\PeriodEnum;
use App\Services\LocalImageGeneratorService;
use App\Services\StoryGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

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
        if (!config('langkahkecil.notification_enable')) {
            return response()->json(['token' => 'disabled']);
        }

        $centrifugo = app(\App\Services\CentrifugoService::class);
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

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/send-verification', [AuthController::class, 'sendVerification'])->name('verification.send');
    Route::post('/verify', [AuthController::class, 'verify'])->name('verification.verify');

});
