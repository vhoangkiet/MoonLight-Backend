<?php

namespace Modules\Product\Media\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class ProductMediaStaging extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = [
        'user_id',
    ];

    protected $table = 'product_media_stagings';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('file')
            ->singleFile()
            ->acceptsMimeTypes([
                'image/jpeg',
                'image/png',
                'image/webp',
                'image/gif',
                'video/mp4',
                'video/webm',
                'video/quicktime',
            ]);
    }
}
