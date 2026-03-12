<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ActivityLogResource;
use App\Models\ActivityLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $logs = ActivityLog::query()
            ->with(['user:id,name,email'])
            ->when(
                $request->filled('action'),
                fn ($query) => $query->where('action', (string) $request->input('action'))
            )
            ->when(
                $request->filled('entity_type'),
                fn ($query) => $query->where('entity_type', (string) $request->input('entity_type'))
            )
            ->when(
                $request->filled('entity_id'),
                fn ($query) => $query->where('entity_id', (int) $request->input('entity_id'))
            )
            ->when(
                $request->filled('actor_id'),
                fn ($query) => $query->where('user_id', (int) $request->input('actor_id'))
            )
            ->latest('id')
            ->paginate(25);

        return response()->json([
            'message' => 'Activity logs fetched successfully.',
            'data' => ActivityLogResource::collection($logs->getCollection()),
            'meta' => [
                'filters' => [
                    'action' => $request->input('action'),
                    'entity_type' => $request->input('entity_type'),
                    'entity_id' => $request->input('entity_id'),
                    'actor_id' => $request->input('actor_id'),
                ],
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
            ],
        ]);
    }
}
