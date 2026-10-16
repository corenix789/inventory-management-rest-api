<?php

namespace App\Services;

use App\Interfaces\ProductRepositoryInterface;
use App\Models\Product;
use App\Services\Product\ProductBarcodeService;
use App\Services\Product\ProductImageService;
use App\Services\Product\ProductQrCodeService;
use App\Services\Product\ProductSkuService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class ProductService
{
    public function __construct(
        protected ProductRepositoryInterface $productRepository,
        protected ProductImageService $imageService,
        protected ProductSkuService $skuService,
        protected ProductBarcodeService $barcodeService,
        protected ProductQrCodeService $qrService
    ) {}

    public function getProducts(array $filters = []): LengthAwarePaginator
    {
        return $this->productRepository->getAll($filters);
    }

    public function getProduct(int $id): Product
    {
        $product = $this->productRepository->findById($id);
        if (! $product) {
            throw new ModelNotFoundException('Product not found.');
        }

        return $product;
    }

    public function createProduct(array $data): Product
    {
        $uploadedImagePath = null;

        try {
            return DB::transaction(function () use ($data, &$uploadedImagePath): Product {
                $data['slug'] = Str::slug($data['name']);
                if (empty($data['sku'])) {
                    $data['sku'] = $this->skuService->generate();
                }
                if (isset($data['image']) && $data['image'] instanceof UploadedFile) {
                    $uploadedImagePath = $this->imageService->upload($data['image']);
                    $data['image'] = $uploadedImagePath;
                }

                $product = $this->productRepository->create($data);
                $product->update([
                    'barcode' => $product->sku,
                    'qr_code' => route('products.show', $product->id),
                ]);

                return $product->refresh();
            });
        } catch (Throwable $exception) {
            $this->imageService->delete($uploadedImagePath);
            Log::error('Product Create Failed', ['exception' => $exception::class]);
            throw $exception;
        }
    }

    public function updateProduct(int $id, array $data): Product
    {
        $oldImagePath = null;
        $uploadedImagePath = null;

        try {
            $updated = DB::transaction(function () use ($id, $data, &$oldImagePath, &$uploadedImagePath): Product {
                $product = $this->getProduct($id);
                if (isset($data['name'])) {
                    $data['slug'] = Str::slug($data['name']);
                }
                if (isset($data['image']) && $data['image'] instanceof UploadedFile) {
                    $oldImagePath = $product->image;
                    $uploadedImagePath = $this->imageService->upload($data['image']);
                    $data['image'] = $uploadedImagePath;
                }

                return $this->productRepository->update($product, $data);
            });
        } catch (Throwable $exception) {
            $this->imageService->delete($uploadedImagePath);
            Log::error('Product Update Failed', ['exception' => $exception::class]);
            throw $exception;
        }

        if ($uploadedImagePath !== null) {
            $this->imageService->delete($oldImagePath);
        }

        return $updated;
    }

    public function deleteProduct(int $id): bool
    {
        return DB::transaction(function () use ($id): bool {
            $product = $this->getProduct($id);

            return $this->productRepository->delete($product);
        });
    }
}
