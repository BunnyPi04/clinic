<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Doctor;

class DoctorController extends Controller
{
    public function index()
    {
        return response()->json(
            Doctor::query()
                ->where('is_active', true)
                ->orderBy('full_name')
                ->get()
        );
    }
}
