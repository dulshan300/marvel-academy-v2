<?php

namespace Tests\Feature\Livewire;

use App\Models\NewsletterSubscriber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class NewsletterSignupTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_email_creates_a_subscriber(): void
    {
        Livewire::test('newsletter-signup')
            ->set('email', 'Parent@Example.co.uk ')
            ->call('subscribe')
            ->assertHasNoErrors()
            ->assertSet('subscribed', true)
            ->assertSee('Thanks for subscribing!');

        $this->assertDatabaseHas('newsletter_subscribers', ['email' => 'parent@example.co.uk', 'unsubscribed_at' => null]);
    }

    public function test_invalid_email_is_rejected(): void
    {
        Livewire::test('newsletter-signup')
            ->set('email', 'not-an-email')
            ->call('subscribe')
            ->assertHasErrors(['email' => 'email'])
            ->assertSet('subscribed', false);

        $this->assertDatabaseCount('newsletter_subscribers', 0);
    }

    public function test_existing_subscriber_is_not_duplicated(): void
    {
        NewsletterSubscriber::factory()->create(['email' => 'parent@example.co.uk']);

        Livewire::test('newsletter-signup')
            ->set('email', 'parent@example.co.uk')
            ->call('subscribe')
            ->assertHasNoErrors();

        $this->assertDatabaseCount('newsletter_subscribers', 1);
    }

    public function test_previously_unsubscribed_email_is_resubscribed(): void
    {
        $subscriber = NewsletterSubscriber::factory()->create(['email' => 'parent@example.co.uk']);
        $subscriber->forceFill(['unsubscribed_at' => now()])->save();

        Livewire::test('newsletter-signup')
            ->set('email', 'parent@example.co.uk')
            ->call('subscribe');

        $this->assertNull($subscriber->fresh()->unsubscribed_at);
    }

    public function test_unsubscribe_link_marks_subscriber_as_unsubscribed(): void
    {
        $subscriber = NewsletterSubscriber::factory()->create();

        $this->get(route('newsletter.unsubscribe', $subscriber->unsubscribe_token))
            ->assertRedirect(route('home'))
            ->assertSessionHas('status');

        $this->assertNotNull($subscriber->fresh()->unsubscribed_at);
    }

    public function test_unsubscribe_link_with_unknown_token_returns_404(): void
    {
        $this->get(route('newsletter.unsubscribe', 'unknown-token'))->assertNotFound();
    }
}
