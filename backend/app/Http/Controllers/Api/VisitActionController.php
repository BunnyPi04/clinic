<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Visit;
use App\Models\VisitAction;
use Illuminate\Http\Request;

class VisitActionController extends Controller
{
    public function index(Visit $visit)
    {
        return response()->json(
            $visit->actions()
                ->with('user')
                ->latest()
                ->get()
        );
    }

    public function store(Request $request, Visit $visit)
    {
        $data = $request->validate([
            'user_id' => ['nullable', 'exists:users,id'],
            'action_type' => ['required', 'string', 'max:50'],
            'action_label' => ['required', 'string', 'max:255'],
            'note' => ['nullable', 'string'],
            'payload' => ['nullable', 'array'],
        ]);

        $data['visit_id'] = $visit->id;

        $action = VisitAction::create($data);

        return response()->json($action, 201);
    }
}
