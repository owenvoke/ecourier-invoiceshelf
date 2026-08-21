<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Ecourier\Http\Controllers\PeppolRecipientController;
use Modules\Ecourier\Http\Controllers\PeppolSettingController;
use Modules\Ecourier\Http\Controllers\PeppolSubmissionController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Mounted by the module's RouteServiceProvider under `api/m/ecourier`, the
| prefix InvoiceShelf 2.x modules use for their own endpoints.
|
*/

Route::get('settings', [PeppolSettingController::class, 'show']);
Route::put('settings', [PeppolSettingController::class, 'update']);

Route::get('recipients', [PeppolRecipientController::class, 'index']);
Route::post('recipients', [PeppolRecipientController::class, 'store']);
Route::delete('recipients/{customerId}', [PeppolRecipientController::class, 'destroy'])->whereNumber('customerId');

Route::get('submissions', [PeppolSubmissionController::class, 'index']);
Route::post('invoices/{invoiceId}/send', [PeppolSubmissionController::class, 'store'])->whereNumber('invoiceId');
