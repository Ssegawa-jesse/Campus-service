<?php

namespace Tests\Unit;

use App\Services\Sms\SmsService;
use PHPUnit\Framework\TestCase;

class HelpersTest extends TestCase
{
    public function test_ugandan_phone_numbers_are_normalised(): void
    {
        $sms = new SmsService();

        $this->assertSame('+256741141433', $sms->normalise('0741141433'));
        $this->assertSame('+256741141433', $sms->normalise('256741141433'));
        $this->assertSame('+256741141433', $sms->normalise('+256 741 141 433'));
        $this->assertSame('+256741141433', $sms->normalise('741141433'));
        $this->assertNull($sms->normalise(''));
    }

    public function test_youtube_links_become_embed_urls(): void
    {
        $this->assertSame('https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ', youtube_embed('https://www.youtube.com/watch?v=dQw4w9WgXcQ'));
        $this->assertSame('https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ', youtube_embed('https://youtu.be/dQw4w9WgXcQ'));
        $this->assertNull(youtube_embed('https://example.com/video.mp4'));
    }
}
