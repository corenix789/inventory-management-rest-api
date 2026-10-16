<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWarehouseRequest;
use App\Http\Requests\UpdateWarehouseRequest;
use App\Http\Resources\WarehouseResource;
use App\Models\Warehouse;
use App\Services\WarehouseService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WarehouseController extends Controller
{
    use ApiResponseTrait;

    protected WarehouseService $warehouseService;

    public function __construct(WarehouseService $warehouseService)
    {
        $this->warehouseService = $warehouseService;
    }

    /**
     * Display a listing of warehouses.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = [
            'search' => $request->search,
            'status' => $request->status,
            'city' => $request->city,
            'country' => $request->country,
            'sort_by' => $request->sort_by ?? 'id',
            'sort_direction' => $request->sort_direction ?? 'desc',
            'per_page' => $request->per_page ?? 10,
        ];

        $warehouses = $this->warehouseService->getAll($filters);

        return $this->success(
            WarehouseResource::collection($warehouses),
            'Warehouses retrieved successfully.'
        );
    }

    /**
     * Store warehouse.
     */
    public function store(StoreWarehouseRequest $request): JsonResponse
    {
        $warehouse = $this->warehouseService->create($request->validated());

        return $this->success(
            new WarehouseResource($warehouse),
            'Warehouse created successfully.',
            201
        );
    }

    /**
     * Display warehouse.
     */
    public function show(Warehouse $warehouse): JsonResponse
    {
        return $this->success(
            new WarehouseResource($warehouse),
            'Warehouse retrieved successfully.'
        );
    }

    /**
     * Update warehouse.
     */
    public function update(
        UpdateWarehouseRequest $request,
        Warehouse $warehouse
    ): JsonResponse {
        $this->warehouseService->update($warehouse, $request->validated());
        $warehouse->refresh();

        return $this->success(
            new WarehouseResource($warehouse),
            'Warehouse updated successfully.'
        );
    }

    /**
     * Soft delete warehouse.
     */
    public function destroy(Warehouse $warehouse): JsonResponse
    {
        $this->warehouseService->delete($warehouse);

        return $this->success(
            null,
            'Warehouse deleted successfully.'
        );
    }

    /**
     * Restore warehouse.
     */
    public function restore(int $id): JsonResponse
    {
        $this->warehouseService->restore($id);
        $warehouse = $this->warehouseService->find($id);

        return $this->success(
            new WarehouseResource($warehouse),
            'Warehouse restored successfully.'
        );
    }

    /**
     * Permanently delete warehouse.
     */
    public function forceDelete(int $id): JsonResponse
    {
        $this->warehouseService->forceDelete($id);

        return $this->success(
            null,
            'Warehouse permanently deleted.'
        );
    }
}
