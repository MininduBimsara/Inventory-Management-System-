<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cupboards\StoreCupboardRequest;
use App\Http\Requests\Cupboards\UpdateCupboardRequest;
use App\Http\Resources\CupboardResource;
use App\Models\Cupboard;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;

class CupboardController extends Controller
{
    public function index(): JsonResponse
    {
        $cupboards = Cupboard::query()
            ->withCount('places')
            ->orderBy('name')
            ->paginate(20);

        return response()->json([
            'message' => 'Cupboards fetched successfully.',
            'data' => CupboardResource::collection($cupboards->getCollection()),
            'meta' => [
                'current_page' => $cupboards->currentPage(),
                'last_page' => $cupboards->lastPage(),
                'per_page' => $cupboards->perPage(),
                'total' => $cupboards->total(),
            ],
        ]);
    }

    public function store(StoreCupboardRequest $request): JsonResponse
    {
        $cupboard = Cupboard::query()->create($request->validated());

        return response()->json([
            'message' => 'Cupboard created successfully.',
            'data' => new CupboardResource($cupboard),
        ], 201);
    }

    public function show(Cupboard $cupboard): JsonResponse
    {
        $cupboard->load([
            'places' => fn ($query) => $query->orderBy('name'),
        ])->loadCount('places');

        return response()->json([
            'message' => 'Cupboard fetched successfully.',
            'data' => new CupboardResource($cupboard),
        ]);
    }

    public function update(UpdateCupboardRequest $request, Cupboard $cupboard): JsonResponse
    {
        $cupboard->update($request->validated());

        return response()->json([
            'message' => 'Cupboard updated successfully.',
            'data' => new CupboardResource($cupboard->fresh()),
        ]);
    }

    public function destroy(Cupboard $cupboard): JsonResponse
    {
        if ($cupboard->places()->exists()) {
            return response()->json([
                'message' => 'Cupboard cannot be deleted because it still has places. Delete or move places first.',
            ], 409);
        }

        try {
            $cupboard->delete();
        } catch (QueryException) {
            return response()->json([
                'message' => 'Cupboard cannot be deleted because it is referenced by other records.',
            ], 409);
        }

        return response()->json([
            'message' => 'Cupboard deleted successfully.',
        ]);
    }
}
