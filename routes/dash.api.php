<?php

use App\Models\License;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

/** Dashboard */
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\VerificationEmailController;
use App\Http\Controllers\Client\ClientController;
use App\Http\Controllers\Inmueble\InmuebleController;
use App\Http\Controllers\LoanApplication\LoanApplicationController;
use App\Http\Controllers\Organization\OrganizationController;
use App\Http\Controllers\Password\ForgotPasswordController;
use App\Http\Controllers\Password\ResetPasswordController;
use App\Http\Controllers\Quotation\QuotationController;
use App\Http\Controllers\UserController;


/** Rutas de Autenticación */
Route::group(['prefix' => 'auth'], function () {
    Route::post('/login', [AuthController::class, 'login'])->name('auth.login');
    Route::post('/signup', [AuthController::class, 'signup'])->name('auth.signup');
    Route::post('/signupinmo', [AuthController::class, 'signUpWithInmobiliaria'])->name('auth.signUpWithInmobiliaria');


    // Rutas protegidas por autenticación
    Route::group([ 'middleware' => 'auth:sanctum' ], function() {
        Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::post('/resend-verification-email', [VerificationEmailController::class, 'resend']);
    });

    Route::post('/verify-email', [VerificationEmailController::class, 'verify']);
    Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLink'])->name('password.reset');
    Route::get('/reset-password/validate-token/{token}', [ResetPasswordController::class, 'validateToken'])->name('password.validateToken');
    Route::post('/reset-password', [ResetPasswordController::class, 'reset'])->name('password.update');
});

/** Rutas de Usuarios */
Route::group(['prefix' => 'users'], function (){
    Route::post('/check-email', [UserController::class, 'checkEmail'])->name('users.checkEmail');
});

/** Rutas Protegidas */
Route::middleware(['auth:dash-api'])->group(function () {

    Route::get('/check-auth', function () {
        return response()->json(['message' => 'Autenticado'], 200);
    });

    /** Usuario */
    Route::get('/profile', function () {
        $user = Auth::user();
        return response()->json(['user' => $user], 200);
    });


    Route::middleware(['verified'])->group(function () {

        /** Clientes */
        Route::group([
            'prefix' => 'clients',
            'middleware' => ['organization.active']
        ], function () {

            // Route::middleware([
            //     'role:admin|user|owner|collaborator',
            //     'permission:collaborator_inmobiliaria',
            // ])->group(function () {
                /** Obtener cotización por cliente */
                Route::get('/{clientId}/quotations/{quotationId}', [QuotationController::class, 'getQuotationByClientId']);

                /** Obtener cotización, cliente y simulador por banco */
                Route::get('/{clientId}/quotations/{quotationId}/banks/{bankName}', [QuotationController::class, 'getBankSimulation']);
                Route::get('/{clientId}/quotations/{quotationId}/banks/{bankName}/amortization', [QuotationController::class, 'getBankSimulationAmortization']);
            // });

            Route::get('/', [ClientController::class, 'getAll']);   /** Obtener todos los clientes */
            Route::get('/id/{id}', [ClientController::class, 'getById']);  /** Obtener cliente por id */
            Route::post('/', [ClientController::class, 'store']); /** Crear cliente */
            Route::post('/create-with-quotation', [ClientController::class, 'storeWithQuotation']); /** Crear cliente con cotización */
            Route::put('/{clientId}/update-with-quotation', [ClientController::class, 'updateWithQuotation']); /** Actualizar cliente con cotización */

        });

        Route::group([
            'prefix' => 'solicitudes',
            'middleware' => ['organization.active']
        ], function (){
            Route::post('/', [LoanApplicationController::class, 'store'])
                ->middleware([
                    'role:admin|user|owner|collaborator',
                    'permission:collaborator_inmobiliaria',
                ]);
        });

        Route::group(['prefix' => 'organization'], function () {

            Route::group(['middleware' => ['role:owner|user|collaborator|admin']], function (){
                Route::get('/', [OrganizationController::class, 'getOrganizationAuth']);
            });

            Route::post('/', [OrganizationController::class, 'newOrganization']);

            Route::group(['middleware' => ['role:owner|user|admin']], function (){
                Route::post('/add-collaborator', [OrganizationController::class, 'addCollaborator']);
            });

            Route::group(['middleware' => ['role:admin']], function (){
                Route::put('/{organizationId}', [OrganizationController::class, 'update']);
            });
        });

        /** Inmuebles */

        Route::middleware('organization.active')->group( function () {
            Route::get('/inmuebles/comprar', [InmuebleController::class, 'getInmueblesComprar']);
            Route::get('/inmuebles/rentar', [InmuebleController::class, 'getInmueblesRentar']);
            Route::get('/inmuebles/preventa', [InmuebleController::class, 'getInmueblesPreventa']);
            Route::get('/inmuebles/inventario', [InmuebleController::class, 'getInmueblesInventario']);
            Route::get('/inmuebles/visor-metropoli', [InmuebleController::class, 'getInmueblesVisorMetropoli']);
        });


        Route::group(['middleware' => ['role:admin']], function (){
            Route::get('/organizations', [OrganizationController::class, 'getAll']);
        });
    });
});

Route::post('/organization/accept-invitation', [OrganizationController::class, 'acceptInvitation']);
