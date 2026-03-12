<?php

namespace App\Http\Controllers\Api;

use App\Enums\InventoryItemStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Items\DecreaseQuantityRequest;
use App\Http\Requests\Items\IncreaseQuantityRequest;
use App\Http\Requests\Items\MarkAsDamagedRequest;
use App\Http\Requests\Items\MarkAsMissingRequest;
use App\Http\Requests\Items\RestoreFromDamagedRequest;
use App\Http\Requests\Items\RestoreFromMissingRequest;
use App\Http\Requests\Items\StoreInventoryItemRequest;
use App\Http\Requests\Items\UpdateInventoryItemRequest;
use App\Http\Resources\InventoryItemResource;
use App\Models\InventoryItem;
use App\Services\InventoryItemImageService;
use App\Services\QuantityManagementService;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ItemController extends Controller
{
    public function __construct(
        private readonly InventoryItemImageService $imageService,
        private readonly QuantityManagementService $quantityService,
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

    /**
     * Increase item quantity
     * 
     * Permission: item.adjust-quantity
     * Endpoint: POST /api/v1/items/{item}/quantity/increase
     */
    public function increaseQuantity(IncreaseQuantityRequest $request, InventoryItem $item): JsonResponse
    {
        try {
            $result = $this->quantityService->increaseQuantity(
                item: $item,
                amount: (int) $request->input('amount'),
                reason: (string) $request->input('reason'),
                userId: auth()->id(),
            );

            return response()->json([
                'message' => 'Item quantity increased successfully.',
                'data' => new InventoryItemResource($result['item']),
                'audit' => [
                    'old_quantity' => $result['old_quantity'],
                    'new_quantity' => $result['new_quantity'],
                    'old_status' => null, // Will be added if status tracking needed
                    'new_status' => $result['new_status'],
                    'status_changed' => $result['status_changed'],
                ],
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'message' => 'Failed to increase quantity.',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Decrease item quantity
     * 
     * Permission: item.adjust-quantity
     * Endpoint: POST /api/v1/items/{item}/quantity/decrease
     */
    public function decreaseQuantity(DecreaseQuantityRequest $request, InventoryItem $item): JsonResponse
    {
        try {
            $result = $this->quantityService->decreaseQuantity(
                item: $item,
                amount: (int) $request->input('amount'),
                reason: (string) $request->input('reason'),
                userId: auth()->id(),
            );

            return response()->json([
                'message' => 'Item quantity decreased successfully.',
                'data' => new InventoryItemResource($result['item']),
                'audit' => [
                    'old_quantity' => $result['old_quantity'],
                    'new_quantity' => $result['new_quantity'],
                    'old_status' => null,
                    'new_status' => $result['new_status'],
                    'status_changed' => $result['status_changed'],
                ],
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'message' => 'Failed to decrease quantity.',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Mark item as Damaged
     * 
     * Permission: status.update
     * Endpoint: POST /api/v1/items/{item}/status/damaged
     * 
     * This is an explicit staff action. Does not automatically affect quantity.
     * Reason is required for audit trail.
     */
    public function markAsDamaged(MarkAsDamagedRequest $request, InventoryItem $item): JsonResponse
    {
        try {
            $result = $this->quantityService->markAsDamaged(
                item: $item,
                reason: (string) $request->input('reason'),
                userId: auth()->id(),
            );

            return response()->json([
                'message' => 'Item marked as damaged successfully.',
                'data' => new InventoryItemResource($result['item']),
                'audit' => [
                    'old_status' => $result['old_status'],
                    'new_status' => $result['new_status'],
                    'reason' => $request->input('reason'),
                ],
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'message' => 'Failed to mark item as damaged.',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Mark item as Missing
     * 
     * Permission: status.update
     * Endpoint: POST /api/v1/items/{item}/status/missing
     * 
     * This is an explicit staff action. Does not automatically affect quantity.
     * Reason is required for audit trail.
     */
    public function markAsMissing(MarkAsMissingRequest $request, InventoryItem $item): JsonResponse
    {
        try {
            $result = $this->quantityService->markAsMissing(
                item: $item,
                reason: (string) $request->input('reason'),
                userId: auth()->id(),
            );

            return response()->json([
                'message' => 'Item marked as missing successfully.',
                'data' => new InventoryItemResource($result['item']),
                'audit' => [
                    'old_status' => $result['old_status'],
                    'new_status' => $result['new_status'],
                    'reason' => $request->input('reason'),
                ],
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'message' => 'Failed to mark item as missing.',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Restore item from Damaged status
     * 
     * Permission: status.update
     * Endpoint: POST /api/v1/items/{item}/status/restore-damaged
     * 
     * Removes the manual Damaged override and recalculates status based on quantity.
     */
    public function restoreFromDamaged(RestoreFromDamagedRequest $request, InventoryItem $item): JsonResponse
    {
        try {
            $result = $this->quantityService->restoreFromDamaged(
                item: $item,
                reason: (string) $request->input('reason'),
                userId: auth()->id(),
            );

            return response()->json([
                'message' => 'Item restored from damaged status successfully.',
                'data' => new InventoryItemResource($result['item']),
                'audit' => [
                    'old_status' => $result['old_status'],
                    'new_status' => $result['new_status'],
                    'restoration_reason' => $result['restoration_reason'],
                ],
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'message' => 'Failed to restore item from damaged status.',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Restore item from Missing status
     * 
     * Permission: status.update
     * Endpoint: POST /api/v1/items/{item}/status/restore-missing
     * 
     * Removes the manual Missing override and recalculates status based on quantity.
     */
    public function restoreFromMissing(RestoreFromMissingRequest $request, InventoryItem $item): JsonResponse
    {
        try {
            $result = $this->quantityService->restoreFromMissing(
                item: $item,
                reason: (string) $request->input('reason'),
                userId: auth()->id(),
            );

            return response()->json([
                'message' => 'Item restored from missing status successfully.',
                'data' => new InventoryItemResource($result['item']),
                'audit' => [
                    'old_status' => $result['old_status'],
                    'new_status' => $result['new_status'],
                    'restoration_reason' => $result['restoration_reason'],
                ],
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'message' => 'Failed to restore item from missing status.',
                'error' => $e->getMessage(),
            ], 422);
        }
    }
}
