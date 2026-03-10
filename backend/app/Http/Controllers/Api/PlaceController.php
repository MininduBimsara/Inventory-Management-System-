<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Places\StorePlaceRequest;
use App\Http\Requests\Places\UpdatePlaceRequest;
use App\Http\Resources\PlaceResource;
use App\Models\Cupboard;
use App\Models\Place;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;

class PlaceController extends Controller
{
    public function index(): JsonResponse
    {
        $places = Place::query()
            ->with('cupboard:id,name,code')
            ->orderBy('name')
            ->paginate(20);

        return response()->json([
            'message' => 'Places fetched successfully.',
            'data' => PlaceResource::collection($places->getCollection()),
            'meta' => [
                'current_page' => $places->currentPage(),
                'last_page' => $places->lastPage(),
                'per_page' => $places->perPage(),
                'total' => $places->total(),
            ],
        ]);
    }

    public function byCupboard(Cupboard $cupboard): JsonResponse
    {
        $places = $cupboard->places()
            ->with('cupboard:id,name,code')
            ->orderBy('name')
            ->paginate(20);

        return response()->json([
            'message' => 'Cupboard places fetched successfully.',
            'data' => PlaceResource::collection($places->getCollection()),
            'meta' => [
                'cupboard' => [
                    'id' => $cupboard->id,
                    'name' => $cupboard->name,
                    'code' => $cupboard->code,
                ],
                'current_page' => $places->currentPage(),
                'last_page' => $places->lastPage(),
                'per_page' => $places->perPage(),
                'total' => $places->total(),
            ],
        ]);
    }

    public function store(StorePlaceRequest $request): JsonResponse
    {
        $place = Place::query()->create($request->validated());

        return response()->json([
            'message' => 'Place created successfully.',
            'data' => new PlaceResource($place->load('cupboard:id,name,code')),
        ], 201);
    }

    public function show(Place $place): JsonResponse
    {
        return response()->json([
            'message' => 'Place fetched successfully.',
            'data' => new PlaceResource($place->load('cupboard:id,name,code')),
        ]);
    }

    public function update(UpdatePlaceRequest $request, Place $place): JsonResponse
    {
        $place->update($request->validated());

        return response()->json([
            'message' => 'Place updated successfully.',
            'data' => new PlaceResource($place->fresh()->load('cupboard:id,name,code')),
        ]);
    }

    public function destroy(Place $place): JsonResponse
    {
        if ($place->inventoryItems()->exists()) {
            return response()->json([
                'message' => 'Place cannot be deleted because it still has inventory items.',
            ], 409);
        }

        try {
            $place->delete();
        } catch (QueryException) {
            return response()->json([
                'message' => 'Place cannot be deleted because it is referenced by other records.',
            ], 409);
        }

        return response()->json([
            'message' => 'Place deleted successfully.',
        ]);
    }
}
