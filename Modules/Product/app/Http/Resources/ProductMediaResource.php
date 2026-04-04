<?php

namespace Modules\Product\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductMediaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $mime = (string) ($this->mime_type ?? '');
        $type = str_starts_with($mime, 'video/') ? 'video' : 'image';

        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'mime_type' => $this->mime_type,
            'type' => $type,
            'url' => $this->getUrl(),
            'order' => $this->order_column,
        ];
    }
}
