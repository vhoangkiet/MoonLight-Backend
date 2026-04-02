<?php

namespace Modules\Product\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductVariantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'sku' => $this->sku,
            'shape' => $this->shape,
            'length' => $this->length,
            'tonal_palette' => $this->tonal_palette,
            'size' => $this->size,
            'price' => $this->price,
            'stock' => $this->stock,
            'status' => $this->status?->value,
            'in_stock' => $this->stock > 0,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
