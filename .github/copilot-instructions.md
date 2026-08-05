# Copilot Instructions — Clinic App

## Architecture Overview

Full-stack clinic management system:
- **Backend**: Laravel 11 API (`backend/`) served via Nginx on `http://localhost:8020`
- **Frontend**: React + Vite (`frontend/`) on `http://localhost:5173`
- **Infrastructure**: Docker Compose — services: `app` (PHP-FPM), `web` (Nginx), `frontend` (Node), `db` (MySQL 8.4), `redis`

All API routes are flat (no auth middleware yet) in `backend/routes/api.php`. Controllers live in `App\Http\Controllers\Api\`.

## Core Domain Model

The central entity is `Visit` (a patient appointment). Data flows outward from it:
```
Patient → Visit → VisitService (billed services)
                → Payment → PaymentTransaction
                → VisitDocument → LabPanel → LabResult
                               → UltrasoundReport
                               → ClinicalNote (via visit)
                → VisitAction (status log)
```
`VisitDocument` carries AI extraction state machine: `uploaded → pending_ai → processing_ai → ai_done / ai_failed → (reviewed)`.

## AI Document Pipeline

`DocumentAiController` manages AI extraction on `VisitDocument`. Mock extraction is used in development (`POST /visit-documents/{id}/ai/mock-extract`). After AI extraction, `DocumentImportController::importStructuredData` parses `ai_structured_data_json` and creates typed records based on `document_type`:
- `blood_test / urine_test / special_test` → `LabPanel` + `LabResult`
- `ultrasound` → `UltrasoundReport`
- `conclusion_prescription` → `ClinicalNote`

## Developer Workflows

**Start everything:**
```bash
docker compose up -d --build
```

**Run artisan commands:**
```bash
docker compose exec app php artisan migrate
docker compose exec app php artisan migrate:fresh --seed
docker compose exec app php artisan tinker
```

**Install/update PHP deps:**
```bash
docker compose exec app composer install
```

**Health check:** `GET http://localhost:8020/api/health`

## Conventions

- **No API Resources or Form Requests** — controllers use inline `$request->validate()` and return `response()->json()` directly.
- **Eager loading**: always `->load([...])` or `->with([...])` relations before returning JSON to avoid N+1.
- **Queue numbers** are auto-incremented per doctor per day at visit creation time (see `VisitController::store`).
- **`$appends` + accessor pattern** used on models for computed fields (e.g., `VisitDocument::getFileUrlAttribute`).
- Models use `$fillable` (not `$guarded`). Always declare new columns in `$fillable`.
- No authentication is implemented yet — all routes are public.

## Key Files

| File | Purpose |
|------|---------|
| `backend/routes/api.php` | All API routes |
| `backend/app/Http/Controllers/Api/` | All controllers |
| `backend/app/Models/Visit.php` | Central domain model |
| `backend/app/Models/VisitDocument.php` | AI pipeline state & file metadata |
| `backend/app/Http/Controllers/Api/DocumentAiController.php` | AI extraction (mock + real) |
| `backend/app/Http/Controllers/Api/DocumentImportController.php` | Structured data → DB records |
| `docker-compose.yml` | Service definitions |
