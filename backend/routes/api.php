<?php

use App\Http\Controllers\Api\DoctorController;
use App\Http\Controllers\Api\PatientController;
use App\Http\Controllers\Api\PatientSourceController;
use App\Http\Controllers\Api\VisitController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\PaymentTransactionController;
use App\Http\Controllers\Api\VisitServiceController;
use App\Http\Controllers\Api\VisitActionController;
use App\Http\Controllers\Api\VisitDocumentController;
use App\Http\Controllers\Api\LabPanelController;
use App\Http\Controllers\Api\LabResultController;

Route::get('/health', function () {
    return response()->json([
        'ok' => true,
        'app' => config('app.name'),
    ]);
});

Route::get('/doctors', [DoctorController::class, 'index']);
Route::get('/patient-sources', [PatientSourceController::class, 'index']);

Route::get('/patients', [PatientController::class, 'index']);
Route::post('/patients', [PatientController::class, 'store']);
Route::get('/patients/{patient}', [PatientController::class, 'show']);

Route::get('/visits', [VisitController::class, 'index']);
Route::post('/visits', [VisitController::class, 'store']);
Route::get('/visits/{visit}', [VisitController::class, 'show']);
Route::get('/visits/{visit}/services', [VisitServiceController::class, 'index']);
Route::post('/visits/{visit}/services', [VisitServiceController::class, 'store']);
Route::put('/visit-services/{visitService}', [VisitServiceController::class, 'update']);
Route::delete('/visit-services/{visitService}', [VisitServiceController::class, 'destroy']);

Route::get('/visits/{visit}/payment', [PaymentController::class, 'showByVisit']);
Route::post('/visits/{visit}/payment', [PaymentController::class, 'store']);
Route::post('/visits/{visit}/payment/refresh-totals', [PaymentController::class, 'refreshTotals']);

Route::post('/payments/{payment}/transactions', [PaymentTransactionController::class, 'store']);
Route::get('/visits/{visit}/actions', [VisitActionController::class, 'index']);
Route::post('/visits/{visit}/actions', [VisitActionController::class, 'store']);

Route::get('/visits/{visit}/documents', [VisitDocumentController::class, 'index']);
Route::post('/visits/{visit}/documents', [VisitDocumentController::class, 'store']);
Route::get('/visit-documents/{visitDocument}', [VisitDocumentController::class, 'show']);
Route::delete('/visit-documents/{visitDocument}', [VisitDocumentController::class, 'destroy']);
Route::get('/visit-documents/{visitDocument}/lab-panels', [LabPanelController::class, 'index']);
Route::post('/visit-documents/{visitDocument}/lab-panels', [LabPanelController::class, 'store']);
Route::get('/lab-panels/{labPanel}', [LabPanelController::class, 'show']);

Route::post('/lab-panels/{labPanel}/results', [LabResultController::class, 'store']);
Route::put('/lab-results/{labResult}', [LabResultController::class, 'update']);
Route::delete('/lab-results/{labResult}', [LabResultController::class, 'destroy']);
