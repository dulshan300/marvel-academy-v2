<?php

namespace Tests\Unit;

use App\Models\LiveSession;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class LiveSessionEmbedTest extends TestCase
{
    /**
     * @return array<string, array{?string, ?string}>
     */
    public static function urls(): array
    {
        $embed = 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ';

        return [
            'watch url' => ['https://www.youtube.com/watch?v=dQw4w9WgXcQ', $embed],
            'watch url with extra params' => ['https://www.youtube.com/watch?feature=share&v=dQw4w9WgXcQ&t=10', $embed],
            'short url' => ['https://youtu.be/dQw4w9WgXcQ', $embed],
            'live url' => ['https://www.youtube.com/live/dQw4w9WgXcQ', $embed],
            'mobile url' => ['https://m.youtube.com/watch?v=dQw4w9WgXcQ', $embed],
            'teams link' => ['https://teams.microsoft.com/l/meetup-join/abc', null],
            'lookalike domain' => ['https://youtube.com.evil.test/watch?v=dQw4w9WgXcQ', null],
            'empty' => ['', null],
            'null' => [null, null],
        ];
    }

    #[DataProvider('urls')]
    public function test_embed_url_is_derived_only_from_youtube_links(?string $url, ?string $expected): void
    {
        $this->assertSame($expected, LiveSession::embedUrlFor($url));
    }
}
