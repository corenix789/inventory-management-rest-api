<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\PurchaseDetailResource;
use App\Models\Purchase;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PurchaseDetailController extends Controller
{
    public function index(Purchase $purchase): AnonymousResourceCollection
    {
        return PurchaseDetailResource::collection(
            $purchase->purchaseDetails()->with('product')->get(),
        );
    }

    public function show(Purchase $purchase, int $detail): PurchaseDetailResource
    {
        return new PurchaseDetailResource(
            $purchase->purchaseDetails()->with('product')->findOrFail($detail),
        );
    }
}
