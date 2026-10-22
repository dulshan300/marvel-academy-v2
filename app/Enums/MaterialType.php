<?php

namespace App\Enums;

enum MaterialType: string
{
    case Pdf = 'pdf';
    case Presentation = 'presentation';
    case Link = 'link';
    case Video = 'video';

    public function label(): string
    {
        return match ($this) {
            self::Pdf => 'PDF',
            self::Presentation => 'Slides',
            self::Link => 'Link',
            self::Video => 'Video',
        };
    }

    /**
     * Whether the material is an uploaded file rather than a URL.
     */
    public function isFile(): bool
    {
        return in_array($this, [self::Pdf, self::Presentation], true);
    }

    /**
     * File extensions accepted for uploads of this type.
     *
     * @return array<int, string>
     */
    public function allowedExtensions(): array
    {
        return match ($this) {
            self::Pdf => ['pdf'],
            self::Presentation => ['ppt', 'pptx'],
            default => [],
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Pdf => '📄',
            self::Presentation => '📊',
            self::Link => '🔗',
            self::Video => '🎬',
        };
    }
}
