<?php

namespace App\Models;

use Database\Factories\DigitalLearningMaterialFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class DigitalLearningMaterial extends Model
{
    /** @use HasFactory<DigitalLearningMaterialFactory> */
    use HasFactory;

    public const CATEGORIES = [
        'Learning Material',
        'Lesson Plan',
        'Assessment/Test Material',
    ];

    public const TYPES = [
        'PDF',
        'PPT',
        'Word Document',
        'Excel Spreadsheet',
        'Video',
        'Interactive Media',
        'Digital Storybook',
        'Learning Worksheet',
        'Educational E-Comic',
        'H5P (HTML5 Package)',
        'Other',
    ];

    protected $fillable = [
        'name',
        'category',
        'type',
        'publisher',
        'link',
        'cover_image_path',
        'attachment_path',
        'description',
        'quality_assured',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'quality_assured' => 'boolean',
            'is_active' => 'boolean',
        ];
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
