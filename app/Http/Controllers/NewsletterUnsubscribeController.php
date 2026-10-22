<?php

namespace App\Http\Controllers;

use App\Models\NewsletterSubscriber;
use Illuminate\Http\RedirectResponse;

class NewsletterUnsubscribeController extends Controller
{
    public function __invoke(string $token): RedirectResponse
    {
        $subscriber = NewsletterSubscriber::where('unsubscribe_token', $token)->firstOrFail();

        if ($subscriber->unsubscribed_at === null) {
            $subscriber->forceFill(['unsubscribed_at' => now()])->save();
        }

        return redirect()->route('home')->with('status', 'You have been unsubscribed from the Marvel Academy newsletter.');
    }
}
