<?php

namespace Modules\Product\Media\Services;

use App\Exceptions\DomainException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Modules\Product\Catalog\Models\Product;
use Modules\Product\Media\Models\ProductMediaStaging;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\Response;

class ProductMediaService
{
    /**
     * @return array{uuid: string, mime_type: ?string, url: string}
     */
    public function uploadStaging(UploadedFile $file, int $userId): array
    {
        $staging = ProductMediaStaging::query()->create(['user_id' => $userId]);

        $media = $staging->addMedia($file)->toMediaCollection('file');

        return [
            'uuid' => (string) $media->uuid,
            'mime_type' => $media->mime_type,
            'url' => $media->getUrl(),
        ];
    }

    /**
     * @param  list<string>  $desiredUuids
     */
    public function syncGallery(Product $product, array $desiredUuids, int $userId): void
    {
        $desiredUuids = array_values(array_unique($desiredUuids));

        DB::transaction(function () use ($product, $desiredUuids, $userId): void {
            $current = $product->getMedia('gallery');

            foreach ($current as $media) {
                if (! in_array((string) $media->uuid, $desiredUuids, true)) {
                    $media->delete();
                }
            }

            foreach ($desiredUuids as $order => $uuid) {
                $media = Media::query()->where('uuid', $uuid)->first();

                if (! $media) {
                    throw new DomainException("Invalid media uuid: {$uuid}", Response::HTTP_UNPROCESSABLE_ENTITY);
                }

                $this->assertCanAttachMedia($media, $product, $userId);

                $stagingToDelete = null;
                if ($media->model instanceof ProductMediaStaging) {
                    $stagingToDelete = $media->model;
                }

                $media->model()->associate($product);
                $media->collection_name = 'gallery';
                $media->order_column = $order + 1;
                $media->save();

                if ($stagingToDelete !== null) {
                    $stagingToDelete->delete();
                }
            }
        });
    }

    private function assertCanAttachMedia(Media $media, Product $product, int $userId): void
    {
        if ($media->model instanceof Product) {
            if ((int) $media->model_id !== (int) $product->id || $media->collection_name !== 'gallery') {
                throw new DomainException('Media belongs to another product.', Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            return;
        }

        if ($media->model instanceof ProductMediaStaging) {
            if ((int) $media->model->user_id !== $userId) {
                throw new DomainException('You cannot use this uploaded file.', Response::HTTP_FORBIDDEN);
            }

            return;
        }

        throw new DomainException('Invalid media for this product.', Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
