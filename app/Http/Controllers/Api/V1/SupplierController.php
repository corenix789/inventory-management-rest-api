<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSupplierRequest;
use App\Http\Requests\UpdateSupplierRequest;
use App\Http\Resources\SupplierCollection;
use App\Http\Resources\SupplierResource;
use App\Models\Supplier;
use App\Services\SupplierService;
use Illuminate\Http\JsonResponse;

class SupplierController extends Controller
{
    protected SupplierService $supplierService;

    public function __construct(SupplierService $supplierService)
    {
        $this->supplierService = $supplierService;
    }

    /**
     * Display a listing of suppliers.
     */
    public function index(): SupplierCollection
    {
        $suppliers = $this->supplierService->paginate(10);

        return new SupplierCollection($suppliers);
    }

    /**
     * Store a newly created supplier.
     */
    public function store(StoreSupplierRequest $request): JsonResponse
    {
        $supplier = $this->supplierService->create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Supplier created successfully.',
            'data' => new SupplierResource($supplier),
        ], 201);
    }

    /**
     * Display the specified supplier.
     */
    public function show(Supplier $supplier): SupplierResource
    {
        return new SupplierResource($supplier);
    }

    /**
     * Update the specified supplier.
     */
    public function update(
        UpdateSupplierRequest $request,
        Supplier $supplier
    ): JsonResponse {
        $supplier = $this->supplierService->update($supplier, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Supplier updated successfully.',
            'data' => new SupplierResource($supplier),
        ]);
    }

    /**
     * Remove the specified supplier.
     */
    public function destroy(Supplier $supplier): JsonResponse
    {
        $this->supplierService->delete($supplier);

        return response()->json([
            'success' => true,
            'message' => 'Supplier deleted successfully.',
        ]);
    }
}
