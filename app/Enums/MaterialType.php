<?php

namespace App\Enums;

enum MaterialType: string
{
    case Document = 'document';
    case Video = 'video';
    case Link = 'link';
    case Worksheet = 'worksheet';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function isFile(): bool
    {
        return in_array($this, [self::Document, self::Worksheet], true);
    }
}
