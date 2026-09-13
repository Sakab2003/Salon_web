<?php

namespace Modules\Service\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class HairstyleModel extends BaseModel
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'hairstyle_models';

    protected $fillable = [
        'name',
        'target_audience',
        'service_id',
        'commission_id',
        'status',
        'description',
    ];

    protected $appends = ['feature_image', 'feature_images', 'feature_image_items'];

    protected $casts = [
        'service_id' => 'integer',
        'commission_id' => 'integer',
        'status' => 'integer',
    ];

    public function service()
    {
        return $this->belongsTo(Service::class, 'service_id');
    }

    public function commission()
    {
        return $this->belongsTo(\Modules\Commission\Models\Commission::class, 'commission_id');
    }

    protected function getFeatureImageItemsAttribute()
    {
        $mediaItems = $this->getMedia('feature_image');

        if ($mediaItems->isEmpty()) {
            return [];
        }

        return $mediaItems->map(function ($media) {
            return [
                'id' => $media->id,
                'url' => $media->getFullUrl(),
            ];
        })->toArray();
    }

    protected function getFeatureImageAttribute()
    {
        $media = $this->getFirstMediaUrl('feature_image');

        return isset($media) && ! empty($media) ? $media : default_feature_image();
    }

    protected function getFeatureImagesAttribute()
    {
        $mediaItems = $this->getMedia('feature_image');

        if ($mediaItems->isEmpty()) {
            return [];
        }

        return $mediaItems->map(function ($media) {
            return $media->getFullUrl();
        })->toArray();
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}
