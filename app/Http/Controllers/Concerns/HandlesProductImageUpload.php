<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Product;
use App\Support\ProductImageStorage;
use App\Support\SecureImageUpload;
use Illuminate\Http\Request;

trait HandlesProductImageUpload
{
    protected function productImageRules(): array
    {
        return SecureImageUpload::rules('image');
    }

    protected function storeProductImage(Request $request, Product $product): void
    {
        if (! $request->hasFile('image')) {
            return;
        }

        ProductImageStorage::delete($product->image_path);

        $product->update([
            'image_path' => ProductImageStorage::store(
                $request->file('image'),
                $product->tenant_id,
                $product->id,
            ),
        ]);
    }
}
