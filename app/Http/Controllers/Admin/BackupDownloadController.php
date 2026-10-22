<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\BackupArchives;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BackupDownloadController extends Controller
{
    /**
     * Download a backup archive, using a short-lived signed URL where the disk supports it.
     */
    public function __invoke(Request $request, BackupArchives $archives): Response
    {
        $path = (string) $request->query('path');

        abort_unless($archives->exists($path), 404);

        $disk = $archives->disk();

        if ($disk->providesTemporaryUrls()) {
            return redirect()->away($disk->temporaryUrl($path, now()->addMinutes(5)));
        }

        return $disk->download($path);
    }
}
