<?php

namespace App\Models;

use Database\Factories\ResourceTitleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class ResourceTitle extends Model
{
    /** @use HasFactory<ResourceTitleFactory> */
    use HasFactory;

    protected $fillable = [
        'learning_resource_type_id',
        'grade_level_id',
        'title',
        'author',
        'publisher',
        'language',
        'subject',
        'volume',
        'edition',
        'copyright_year',
        'pages',
        'isbn',
        'description',
        'cover_image_path',
        'attachment_path',
        'media_url',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'copyright_year' => 'integer',
            'pages' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function learningResourceType(): BelongsTo
    {
        return $this->belongsTo(LearningResourceType::class);
    }

    public function gradeLevel(): BelongsTo
    {
        return $this->belongsTo(GradeLevel::class);
    }

    public function learningResources(): HasMany
    {
        return $this->hasMany(LearningResource::class);
    }

    public function coverImageUrl(): ?string
    {
        return $this->cover_image_path ? Storage::disk('public')->url($this->cover_image_path) : null;
    }

    public function attachmentUrl(): ?string
    {
        return $this->attachment_path ? Storage::disk('public')->url($this->attachment_path) : null;
    }
}
