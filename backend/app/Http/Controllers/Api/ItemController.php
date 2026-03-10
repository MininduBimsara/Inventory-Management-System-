<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Items\StoreInventoryItemRequest;
use App\Http\Requests\Items\UpdateInventoryItemRequest;
use App\Http\Resources\InventoryItemResource;
use App\Models\InventoryItem;
use App\Services\InventoryItemImageService;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ItemController extends Controller
{
    public function __construct(
        private readonly InventoryItemImageService $imageService,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $items = InventoryItem::query()
            ->with(['place:id,cupboard_id,name,code', 'place.cupboard:id,name,code'])
            ->when(
                $request->filled('place_id'),
                fn ($query) => $query->where('place_id', (int) $request->input('place_id'))
            )
            ->when(
                $request->filled('cupboard_id'),
                fn ($query) => $query->whereHas('place', fn ($placeQuery) => $placeQuery->where('cupboard_id', (int) $request->input('cupboard_id')))
            )
            ->orderBy('name')
            ->paginate(20);

        return response()->json([
            'message' => 'Items fetched successfully.',
            'data' => InventoryItemResource::collection($items->getCollection()),
            'meta' => [
                'filters' => [
                    'place_id' => $request->input('place_id'),
                    'cupboard_id' => $request->input('cupboard_id'),
                ],
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
            ],
        ]);
    }

    public function store(StoreInventoryItemRequest $request): JsonResponse
    {
        $payload = $request->validated();

        if ($request->hasFile('image')) {
            $payload['image_path'] = $this->imageService->store($request->file('image'));
        }

        unset($payload['image']);

        $item = InventoryItem::query()->create($payload);

        return response()->json([
            'message' => 'Item created successfully.',
            'data' => new InventoryItemResource($item->load(['place:id,cupboard_id,name,code', 'place.cupboard:id,name,code'])),
        ], 201);
    }

    public function show(InventoryItem $item): JsonResponse
    {
        return response()->json([
            'message' => 'Item fetched successfully.',
            'data' => new InventoryItemResource($item->load(['place:id,cupboard_id,name,code', 'place.cupboard:id,name,code'])),
        ]);
    }

    public function update(UpdateInventoryItemRequest $request, InventoryItem $item): JsonResponse
    {
        $payload = $request->validated();

        if ($request->hasFile('image')) {
            $payload['image_path'] = $this->imageService->replace($item->image_path, $request->file('image'));
        }

        unset($payload['image']);

        $item->update($payload);

        return response()->json([
            'message' => 'Item updated successfully.',
            'data' => new InventoryItemResource($item->fresh()->load(['place:id,cupboard_id,name,code', 'place.cupboard:id,name,code'])),
        ]);
    }

    public function destroy(InventoryItem $item): JsonResponse
    {
        if ($item->borrowTransactionItems()->exists()) {
            return response()->json([
                'message' => 'Item cannot be deleted because it is already used in borrow transactions.',
            ], 409);
        }

        if ($item->quantity > 0) {
            return response()->json([
                'message' => 'Item with quantity greater than zero cannot be deleted. Set quantity to zero first.',
            ], 409);
        }

        try {
            $item->delete();
        } catch (QueryException) {
            return response()->json([
                'message' => 'Item cannot be deleted because it is referenced by other records.',
            ], 409);
        }

        return response()->json([
            'message' => 'Item archived successfully.',
        ]);
    }
}
