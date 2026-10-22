<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Material;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class MaterialDownloadController extends Controller
{
    /**
     * Serve a material to an authorised user: external links redirect, files
     * stream from private storage through a short-lived URL where supported.
     */
    public function __invoke(Material $material): RedirectResponse|Response
    {
        Gate::authorize('download', $material);

        $media = $material->getFirstMedia('file');

        if ($media === null) {
            abort_if($material->external_url === null, 404);

            return redirect()->away($material->external_url);
        }

        $disk = Storage::disk($media->disk);

        if ($disk->providesTemporaryUrls()) {
            return redirect()->away($media->getTemporaryUrl(now()->addMinutes(10)));
        }

        return $media->toResponse(request());
    }
}
