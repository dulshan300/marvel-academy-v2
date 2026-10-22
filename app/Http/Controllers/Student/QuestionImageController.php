<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Question;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class QuestionImageController extends Controller
{
    /**
     * Serve a picture question's image from private storage.
     */
    public function __invoke(Question $question): RedirectResponse|Response
    {
        Gate::authorize('viewImage', $question);

        $media = $question->getFirstMedia('image');

        abort_if($media === null, 404);

        if (Storage::disk($media->disk)->providesTemporaryUrls()) {
            return redirect()->away($media->getTemporaryUrl(now()->addMinutes(30)));
        }

        return $media->toResponse(request());
    }
}
