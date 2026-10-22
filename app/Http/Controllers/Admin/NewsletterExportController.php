<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NewsletterSubscriber;
use Symfony\Component\HttpFoundation\StreamedResponse;

class NewsletterExportController extends Controller
{
    /**
     * Download active newsletter subscribers as CSV.
     */
    public function __invoke(): StreamedResponse
    {
        return response()->streamDownload(function (): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['email', 'name', 'source', 'subscribed_at']);

            NewsletterSubscriber::whereNull('unsubscribed_at')
                ->orderBy('id')
                ->lazy()
                ->each(fn (NewsletterSubscriber $subscriber) => fputcsv($handle, [
                    $this->safeCell($subscriber->email),
                    $this->safeCell((string) $subscriber->name),
                    $subscriber->source,
                    $subscriber->created_at->toDateTimeString(),
                ]));

            fclose($handle);
        }, 'newsletter-subscribers-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv']);
    }

    /**
     * Neutralise spreadsheet formula injection in user-supplied values.
     */
    private function safeCell(string $value): string
    {
        return preg_match('/^[=+\-@\t\r]/', $value) === 1 ? "'".$value : $value;
    }
}
