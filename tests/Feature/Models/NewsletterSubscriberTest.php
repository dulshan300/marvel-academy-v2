<?php

namespace Tests\Feature\Models;

use App\Models\NewsletterSubscriber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewsletterSubscriberTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_a_subscriber_generates_a_unique_unsubscribe_token(): void
    {
        $first = NewsletterSubscriber::factory()->create();
        $second = NewsletterSubscriber::factory()->create();

        $this->assertSame(64, strlen($first->unsubscribe_token));
        $this->assertNotSame($first->unsubscribe_token, $second->unsubscribe_token);
    }

    public function test_unsubscribe_token_is_hidden_from_serialisation(): void
    {
        $subscriber = NewsletterSubscriber::factory()->create();

        $this->assertArrayNotHasKey('unsubscribe_token', $subscriber->toArray());
    }
}
