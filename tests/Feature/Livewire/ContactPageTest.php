<?php

namespace Tests\Feature\Livewire;

use App\Mail\ContactMessageReceived;
use App\Models\ContactMessage;
use App\Models\Faq;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class ContactPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_shows_published_faqs_only(): void
    {
        Faq::factory()->create(['question' => 'Do you teach GCSE?']);
        Faq::factory()->create(['question' => 'Hidden internal question?', 'is_published' => false]);

        $this->get(route('contact'))
            ->assertOk()
            ->assertSee('Do you teach GCSE?')
            ->assertDontSee('Hidden internal question?');
    }

    public function test_valid_enquiry_is_stored_and_emailed_to_support(): void
    {
        Mail::fake();

        Livewire::test('pages::contact')
            ->set('name', 'Jo Bloggs')
            ->set('email', 'jo@example.co.uk')
            ->set('phone', '07700 900123')
            ->set('subject', 'Sibling discount')
            ->set('message', 'Do you offer a discount for two children?')
            ->call('send')
            ->assertHasNoErrors()
            ->assertSet('sent', true)
            ->assertSet('message', '');

        $contactMessage = ContactMessage::sole();
        $this->assertSame('jo@example.co.uk', $contactMessage->email);

        Mail::assertQueued(ContactMessageReceived::class, function (ContactMessageReceived $mail) use ($contactMessage) {
            return $mail->hasTo(config('marvel.contact.email'))
                && $mail->contactMessage->is($contactMessage)
                && $mail->hasReplyTo('jo@example.co.uk');
        });
    }

    public function test_missing_required_fields_are_rejected(): void
    {
        Mail::fake();

        Livewire::test('pages::contact')
            ->call('send')
            ->assertHasErrors(['name' => 'required', 'email' => 'required', 'subject' => 'required', 'message' => 'required']);

        $this->assertDatabaseCount('contact_messages', 0);
        Mail::assertNothingQueued();
    }

    public function test_invalid_email_and_phone_are_rejected(): void
    {
        Livewire::test('pages::contact')
            ->set('email', 'not-an-email')
            ->set('phone', 'call me maybe')
            ->call('send')
            ->assertHasErrors(['email' => 'email', 'phone' => 'regex']);
    }

    public function test_fourth_enquiry_from_the_same_ip_within_ten_minutes_is_throttled(): void
    {
        Mail::fake();

        $component = Livewire::test('pages::contact');

        foreach (range(1, 4) as $attempt) {
            $component
                ->set('name', 'Jo Bloggs')
                ->set('email', 'jo@example.co.uk')
                ->set('subject', "Question {$attempt}")
                ->set('message', 'A sufficiently long message body.')
                ->call('send');
        }

        $component->assertHasErrors('message');
        $this->assertDatabaseCount('contact_messages', 3);
    }
}
