<?php

namespace App\Http\Controllers\Api;

use App\Enums\BorrowTransactionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Borrows\ReturnBorrowItemsRequest;
use App\Http\Requests\Borrows\StoreBorrowTransactionRequest;
use App\Http\Resources\BorrowTransactionResource;
use App\Models\BorrowTransaction;
use App\Services\BorrowReturnService;
use App\Services\BorrowTransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class BorrowController extends Controller
{
    public function __construct(
        private readonly BorrowTransactionService $borrowTransactionService,
        private readonly BorrowReturnService $borrowReturnService,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $this->borrowTransactionService->refreshOverdueTransactions();

        $borrows = BorrowTransaction::query()
            ->with([
                'creator:id,name,email',
                'borrowTransactionItems',
                'borrowTransactionItems.inventoryItem:id,place_id,name,code,quantity,status',
            ])
            ->when(
                $request->filled('status'),
                fn ($query) => $query->where('status', (string) $request->input('status'))
            )
            ->when(
                $request->boolean('overdue_only'),
                fn ($query) => $query->overdue()
            )
            ->when(
                $request->filled('borrower'),
                fn ($query) => $query->where(function ($searchQuery) use ($request): void {
                    $borrower = (string) $request->input('borrower');

                    $searchQuery
                        ->where('borrower_name', 'like', "%{$borrower}%")
                        ->orWhere('borrower_contact', 'like', "%{$borrower}%");
                })
            )
            ->latest('id')
            ->paginate(20);

        return response()->json([
            'message' => 'Borrow transactions fetched successfully.',
            'data' => BorrowTransactionResource::collection($borrows->getCollection()),
            'meta' => [
                'filters' => [
                    'status' => $request->input('status'),
                    'borrower' => $request->input('borrower'),
                    'overdue_only' => $request->boolean('overdue_only'),
                ],
                'allowed_statuses' => BorrowTransactionStatus::values(),
                'current_page' => $borrows->currentPage(),
                'last_page' => $borrows->lastPage(),
                'per_page' => $borrows->perPage(),
                'total' => $borrows->total(),
            ],
        ]);
    }

    public function store(StoreBorrowTransactionRequest $request): JsonResponse
    {
        try {
            $borrow = $this->borrowTransactionService->createBorrowTransaction(
                payload: $request->validated(),
                userId: (int) $request->user()->getAuthIdentifier(),
            );

            return response()->json([
                'message' => 'Borrow transaction created successfully.',
                'data' => new BorrowTransactionResource($borrow),
            ], 201);
        } catch (InvalidArgumentException $exception) {
            return response()->json([
                'message' => 'Failed to create borrow transaction.',
                'error' => $exception->getMessage(),
            ], 422);
        }
    }

    public function show(BorrowTransaction $borrow): JsonResponse
    {
        $this->borrowTransactionService->refreshOverdueTransactions();

        return response()->json([
            'message' => 'Borrow transaction fetched successfully.',
            'data' => new BorrowTransactionResource(
                $borrow->fresh([
                    'creator:id,name,email',
                    'borrowTransactionItems',
                    'borrowTransactionItems.inventoryItem:id,place_id,name,code,quantity,status',
                    'borrowTransactionItems.inventoryItem.place:id,cupboard_id,name,code',
                ])
            ),
        ]);
    }

    public function returnItems(ReturnBorrowItemsRequest $request, BorrowTransaction $borrow): JsonResponse
    {
        try {
            $updatedBorrow = $this->borrowReturnService->returnItems(
                borrow: $borrow,
                payload: $request->validated(),
                userId: (int) $request->user()->getAuthIdentifier(),
            );

            return response()->json([
                'message' => 'Borrow return processed successfully.',
                'data' => new BorrowTransactionResource($updatedBorrow),
            ]);
        } catch (InvalidArgumentException $exception) {
            return response()->json([
                'message' => 'Failed to process return.',
                'error' => $exception->getMessage(),
            ], 422);
        }
    }
}
