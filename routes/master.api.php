<?php

use App\Http\Controllers\Admin\AdminController;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\ForgotPasswordController;
use App\Http\Controllers\Admin\ResetPasswordController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Modules\Admin\LoanApplication\CobranzaController;

use App\Http\Controllers\Client\ClientController as DashboardClientController;
use App\Http\Controllers\Modules\Admin\LoanApplication\SolicitudesController;

use App\Http\Controllers\Modules\Admin\Prospecto\ProspectoController;
use App\Http\Controllers\Modules\Client\LoanApplication\BuroController;
use App\Http\Controllers\Quotation\QuotationController as DashboardQuotationController;

use App\Http\Controllers\Modules\Client\LoanApplication\LoanApplicationController;



Route::group(['prefix' => 'auth'], function () {
    Route::post('/login', [AuthController::class, 'login'])->name('admin.auth.login');
    // Rutas protegidas por autenticación
    Route::group(['middleware' => 'auth:sanctum'], function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('admin.auth.logout');
    });

    Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLink'])->name('admin.password.reset');

    Route::get('/reset-password/validate-token/{token}', [ResetPasswordController::class, 'validateToken'])->name('admin.password.validateToken');
    Route::post('/reset-password', [ResetPasswordController::class, 'reset'])->name('admin.password.update');
});

Route::middleware(['auth:admin-api'])->group(function () {

    /** Usuarios */
    Route::group(['prefix' => 'users'], function () {
        Route::get('/', [AdminController::class, 'getAll']);
        Route::get('/{admin}', [AdminController::class, 'getById']);
        Route::post('/', [AdminController::class, 'store']);
        Route::put('/{admin}', [AdminController::class, 'update']);
        Route::delete('/{admin}', [AdminController::class, 'destroy']);
    });

    Route::get('/check-auth', function () {
        return response()->json(['message' => 'Autenticado'], 200);
    });

    /** Clientes */
    Route::group([ 'prefix' => 'clients' ], function () {
        Route::get('/', [DashboardClientController::class, 'getAll']);
        /** Obtener todos los clientes */
        Route::get('/id/{id}', [DashboardClientController::class, 'getById']);
        /** Obtener cliente por id */
        Route::post('/', [DashboardClientController::class, 'adminStore']);
        /** Crear cliente */
        Route::put('/set-score/{clientId}', [BuroController::class, 'requestScoreByAdmin']);
        /** Solicitar score */
        Route::post('/create-with-quotation', [DashboardClientController::class, 'storeWithQuotation']);
        /** Crear cliente con cotización */
        Route::post('/{clientId}/quotation', [DashboardClientController::class, 'adminStoreQuotization']);
        /** Crear cotización */
        Route::put('/{clientId}/update-with-quotation', [DashboardClientController::class, 'updateWithQuotation']);
        /** Actualizar cliente con cotización */
        Route::get('/{clientId}/quotations/{quotationId}', [DashboardQuotationController::class, 'getQuotationByClientId']);
        /** Obtener cotización, cliente y simulador por banco */
        Route::get('/{clientId}/quotations/{quotationId}/banks/{bankName}', [DashboardQuotationController::class, 'getBankSimulation']);
        Route::get('/{clientId}/quotations/{quotationId}/banks/{bankName}/amortization', [DashboardQuotationController::class, 'getBankSimulationAmortization']);
    });

    Route::group(['prefix' => 'loan-applications'], function () {
        Route::post('/{loanApplicationId}/upload-first', [SolicitudesController::class, 'uploadSolicitudAndAnexo']);
        Route::put('/{loanApplicationId}/confirm-sol', [SolicitudesController::class, 'confirmSolicitudAndAnexo']);

        Route::put('/{loanApplicationId}/stage-inform', [SolicitudesController::class, 'stageInform']);
        Route::get('/{loanApplicationId}/stage-inform', [SolicitudesController::class, 'getStageInform']);

        Route::post('/{loanApplicationId}/upload-attachments-cobranza', [CobranzaController::class, 'uploadAttachmentsCobranza']);
        Route::put('/{loanApplicationId}/probably-pay-date', [CobranzaController::class, 'probablyPayDate']);
        Route::get('/clients-files-stage-ten', [CobranzaController::class, 'getClientsFilesStageTen']);
        Route::get('/clients-files-stage-nine', [CobranzaController::class, 'getClientsFilesStageNine']);
        Route::post('/', [LoanApplicationController::class, 'store'])
            ->name('client.loanApplication.store');
        Route::get('/{id}', [SolicitudesController::class, 'getLoanApplicationsById'])
            ->name('admin.loanApplication.getLoanApplicationsById');
    });

    Route::group(['prefix' => 'prospectos'], function () {
        Route::get('/', [ProspectoController::class, 'getAll']);
        Route::get('/{id}', [ProspectoController::class, 'getById']);
        Route::post('/', [ProspectoController::class, 'store']);
        Route::put('/{prospecto}', [ProspectoController::class, 'update']);
        Route::delete('/{prospecto}', [ProspectoController::class, 'destroy']);
    });
});
