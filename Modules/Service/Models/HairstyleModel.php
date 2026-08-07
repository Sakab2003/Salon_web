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
        'service_id',
        'status',
        'description',
    ];

    protected $appends = ['feature_image'];

    protected $casts = [
        'service_id' => 'integer',
        'status' => 'integer',
    ];

    public function service()
    {
        return $this->belongsTo(Service::class, 'service_id');
    }

    protected function getFeatureImageAttribute()
    {
        $media = $this->getFirstMediaUrl('feature_image');

        return isset($media) && ! empty($media) ? $media : default_feature_image();
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}
