<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PatientSource;

class PatientSourceController extends Controller
{
    public function index()
    {
        return response()->json(
            PatientSource::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get()
        );
    }
}
