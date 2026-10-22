<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\EmailPreviews;
use Illuminate\Http\Response;

class EmailPreviewController extends Controller
{
    /**
     * Render an email template for the admin previewer's iframe.
     */
    public function __invoke(string $template, EmailPreviews $previews): Response
    {
        abort_unless(array_key_exists($template, EmailPreviews::templates()), 404);

        $html = $previews->render($template)
            ?? '<p style="font-family:sans-serif;color:#475569;padding:2rem">There is no data to preview this email with yet.</p>';

        return response($html)->header('X-Frame-Options', 'SAMEORIGIN');
    }
}
