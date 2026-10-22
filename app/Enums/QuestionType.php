<?php

namespace App\Enums;

enum QuestionType: string
{
    /** A written question with answer options. */
    case Text = 'text';

    /** An image (graph, diagram, shape...) with a description, then the question and options. */
    case Picture = 'picture';

    public function label(): string
    {
        return match ($this) {
            self::Text => 'Multiple choice',
            self::Picture => 'Picture question',
        };
    }

    /**
     * Image file extensions accepted for picture questions. SVG is excluded
     * because it can carry scripts.
     *
     * @return array<int, string>
     */
    public static function imageExtensions(): array
    {
        return ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    }
}
