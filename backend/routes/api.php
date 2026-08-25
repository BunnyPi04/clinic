<?php

use App\Http\Controllers\Api\BillingOptionController;
use App\Http\Controllers\Api\ClinicalNoteController;
use App\Http\Controllers\Api\DoctorController;
use App\Http\Controllers\Api\DocumentAiController;
use App\Http\Controllers\Api\DocumentImportController;
use App\Http\Controllers\Api\LabBillingController;
use App\Http\Controllers\Api\LabPanelController;
use App\Http\Controllers\Api\LabResultController;
use App\Http\Controllers\Api\PatientController;
use App\Http\Controllers\Api\PatientSourceController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\PaymentTransactionController;
use App\Http\Controllers\Api\ServiceCatalogController;
use App\Http\Controllers\Api\UltrasoundReportController;
use App\Http\Controllers\Api\VisitActionController;
use App\Http\Controllers\Api\VisitController;
use App\Http\Controllers\Api\VisitDocumentController;
use App\Http\Controllers\Api\VisitServiceBatchController;
use App\Http\Controllers\Api\VisitServiceController;
use App\Http\Controllers\Api\DepartmentController;
use App\Http\Controllers\Api\DoctorManagementController;
use App\Http\Controllers\Api\DoctorScheduleController;
use App\Http\Controllers\Api\HospitalController;
use Illuminate\Support\Facades\Route;

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

Route::post('/visits/{visit}/lab-billing/sync', [LabBillingController::class, 'syncToVisitService']);

Route::get('/visits/{visit}/clinical-notes', [ClinicalNoteController::class, 'index']);
Route::post('/visits/{visit}/clinical-notes', [ClinicalNoteController::class, 'store']);
Route::get('/clinical-notes/{clinicalNote}', [ClinicalNoteController::class, 'show']);
Route::put('/clinical-notes/{clinicalNote}', [ClinicalNoteController::class, 'update']);
Route::delete('/clinical-notes/{clinicalNote}', [ClinicalNoteController::class, 'destroy']);

Route::get('/visit-documents/{visitDocument}/ultrasound-reports', [UltrasoundReportController::class, 'index']);
Route::post('/visit-documents/{visitDocument}/ultrasound-reports', [UltrasoundReportController::class, 'store']);
Route::get('/ultrasound-reports/{ultrasoundReport}', [UltrasoundReportController::class, 'show']);
Route::put('/ultrasound-reports/{ultrasoundReport}', [UltrasoundReportController::class, 'update']);
Route::delete('/ultrasound-reports/{ultrasoundReport}', [UltrasoundReportController::class, 'destroy']);

Route::get('/visit-documents/{visitDocument}/ai', [DocumentAiController::class, 'show']);
Route::post('/visit-documents/{visitDocument}/ai/mock-extract', [DocumentAiController::class, 'runMockExtraction']);
Route::put('/visit-documents/{visitDocument}/ai', [DocumentAiController::class, 'updateExtraction']);
Route::post('/visit-documents/{visitDocument}/ai/review', [DocumentAiController::class, 'markReviewed']);
Route::post('/visit-documents/{visitDocument}/import-structured-data', [DocumentImportController::class, 'importStructuredData']);
Route::get(
    '/visits/{visit}/billing-options',
    [BillingOptionController::class, 'index']
);

Route::put(
    '/visits/{visit}/services/batch',
    [VisitServiceBatchController::class, 'replace']
);

Route::get('/service-catalogs', [
    ServiceCatalogController::class,
    'index',
]);

Route::post('/service-catalogs', [
    ServiceCatalogController::class,
    'store',
]);

Route::get('/service-catalogs/{serviceCatalog}', [
    ServiceCatalogController::class,
    'show',
]);

Route::put('/service-catalogs/{serviceCatalog}', [
    ServiceCatalogController::class,
    'update',
]);

Route::get('/hospitals', [
    HospitalController::class,
    'index',
]);

Route::post('/hospitals', [
    HospitalController::class,
    'store',
]);

Route::get('/hospitals/{hospital}', [
    HospitalController::class,
    'show',
]);

Route::put('/hospitals/{hospital}', [
    HospitalController::class,
    'update',
]);

Route::get('/departments', [
    DepartmentController::class,
    'index',
]);

Route::post('/departments', [
    DepartmentController::class,
    'store',
]);

Route::put('/departments/{department}', [
    DepartmentController::class,
    'update',
]);

Route::get('/staff/doctors', [
    DoctorManagementController::class,
    'index',
]);

Route::get('/staff/doctors/{doctor}', [
    DoctorManagementController::class,
    'show',
]);

Route::put('/staff/doctors/{doctor}', [
    DoctorManagementController::class,
    'update',
]);

Route::get('/staff/doctors/{doctor}/schedules', [
    DoctorScheduleController::class,
    'index',
]);

Route::post('/staff/doctors/{doctor}/schedules', [
    DoctorScheduleController::class,
    'storeSchedule',
]);

Route::put('/doctor-schedules/{doctorSchedule}', [
    DoctorScheduleController::class,
    'updateSchedule',
]);

Route::post('/staff/doctors/{doctor}/schedule-exceptions', [
    DoctorScheduleController::class,
    'storeException',
]);

Route::get('/doctor-schedules/by-date', [
    DoctorScheduleController::class,
    'doctorsForDate',
]);
