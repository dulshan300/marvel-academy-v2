<?php

namespace App\Enums;

enum KeyStage: string
{
    case Ks2 = 'ks2';
    case Ks3 = 'ks3';
    case Gcse = 'gcse';
    case ALevel = 'a_level';

    public function label(): string
    {
        return match ($this) {
            self::Ks2 => 'KS2',
            self::Ks3 => 'KS3',
            self::Gcse => 'GCSE',
            self::ALevel => 'A-Level',
        };
    }

    /**
     * Typical UK school years covered by the key stage.
     */
    /**
     * School years in the key stage.
     *
     * @return array<int, int>
     */
    public function years(): array
    {
        return match ($this) {
            self::Ks2 => range(3, 6),
            self::Ks3 => range(7, 9),
            self::Gcse => range(10, 11),
            self::ALevel => range(12, 13),
        };
    }

    public function yearRange(): string
    {
        return match ($this) {
            self::Ks2 => 'Years 3–6',
            self::Ks3 => 'Years 7–9',
            self::Gcse => 'Years 10–11',
            self::ALevel => 'Years 12–13',
        };
    }
}
