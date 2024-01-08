<?php


use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/** Client */
use App\Http\Controllers\Modules\Client\Auth\AuthController;
use App\Http\Controllers\Modules\Client\Auth\ForgotPasswordController;
use App\Http\Controllers\Modules\Client\Auth\ResetPasswordController;
use App\Http\Controllers\Modules\Client\Auth\VerificationEmailController;
use App\Http\Controllers\Modules\Client\FirstStepController;
use App\Http\Controllers\Modules\Client\LoanApplication\BuroController;
use App\Http\Controllers\Modules\Client\LoanApplication\LoanApplicationController;
use App\Http\Controllers\Modules\Client\Quotation\QuotationController;
use App\Http\Controllers\Modules\Client\Profile\ProfileController;


Route::post('first-step', [FirstStepController::class, 'firstStep'])
    ->name('client.firstStep');

Route::group(['prefix' => 'auth'], function () {
    Route::group(['middleware' => 'auth:sanctum'], function() {
        Route::post('/logout', [AuthController::class, 'logout'])
            ->name('client.auth.logout');
        Route::post('/resend-verification-email', [VerificationEmailController::class, 'resend'])
            ->name('client.auth.resend');
    });

    Route::post('/login', [AuthController::class, 'login'])
        ->name('client.auth.login');
    Route::post('/signup', [AuthController::class, 'signup'])
        ->name('client.auth.signup');

    Route::post('/verify-email', [VerificationEmailController::class, 'verify'])
        ->name('client.auth.verify');

    Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLink'])
        ->name('client.password.reset');
    Route::get('/reset-password/validate-token/{token}', [ResetPasswordController::class, 'validateToken'])
        ->name('client.password.validateToken');
    Route::post('/reset-password', [ResetPasswordController::class, 'reset'])
        ->name('client.password.update');
});

Route::middleware(['auth:client-api'])->group(function () {
    Route::get('/profile', function (){
        $user = Auth::user();
        return response()->json(['user' => $user ], 200);
    });

    Route::put('/profile', [ProfileController::class, 'update'])
        ->name('client.profile.update');

    Route::get('/check-auth', function () {
        return response()->json(['message' => 'Autenticado'], 200);
    });

    Route::middleware(['verified'])->group(function () {
        Route::get('my-quotations', [QuotationController::class, 'getMyQuotations'])
            ->name('client.myQuotations');

        Route::post('/quotations', [QuotationController::class, 'store'])
            ->name('client.storeQuotation');

        Route::get('/quotations/{id}', [QuotationController::class, 'getQuotationById'])
            ->name('client.getQuotationById');

        Route::get('/quotations/{id}/banks/{bankName}', [QuotationController::class, 'getBankSimulation'])
            ->name('client.getBankSimulation');

        Route::get('/quotations/{id}/banks/{bankName}/amortization', [QuotationController::class, 'getBankSimulationAmortization'])
            ->name('client.getBankSimulationAmortization');


        Route::group(['prefix' => 'loan-applications'], function () {
            Route::post('/check-buro-score-and-create', [BuroController::class, 'checkBuroScoreAndCreate'])
                ->name('client.loanApplication.checkBuroScoreAndCreate');

            Route::post('/', [LoanApplicationController::class, 'store'])
                ->name('client.loanApplication.store');

            Route::get('/', [LoanApplicationController::class, 'getMyLoanApplications'])
                ->name('client.loanApplication.getMyLoanApplications');

            Route::get('/{id}', [LoanApplicationController::class, 'getLoanApplicationsById'])
                ->name('client.loanApplication.getLoanApplicationsById');

            Route::group(['prefix' => 'stage-one'], function (){
                Route::post('/complete-stage', [LoanApplicationController::class, 'completeStageOne'])
                    ->name('client.loanApplication.completeStageOne');
            });

            Route::group(['prefix' => 'stage-six'], function (){
                Route::put('/complete-stage', [LoanApplicationController::class, 'completeStageSix'])
                    ->name('client.loanApplication.completeStageSix');
            });

        });

    });
});

Route::post('/check-email', [VerificationEmailController::class, 'checkEmail'])->name('client.auth.checkEmail');
