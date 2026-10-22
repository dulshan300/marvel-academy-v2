<?php

namespace App\Mail;

use App\Models\Child;
use App\Models\LiveSession;
use App\Models\Material;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

/**
 * Sent seven days before a live session with preparation materials attached.
 */
class SessionPreparationReminder extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Attachments above this combined size are linked instead of attached.
     */
    public const MAX_ATTACHMENT_BYTES = 15 * 1024 * 1024;

    public function __construct(public LiveSession $session, public Child $child) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Get ready: {$this->child->first_name}'s {$this->session->course->title} lesson on {$this->session->starts_at->format('l j F')}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.session-preparation',
            with: [
                'materials' => $this->preparationMaterials(),
                'attachedIds' => $this->attachableMaterials()->pluck('id')->all(),
            ],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return $this->attachableMaterials()
            ->map(function (Material $material): Attachment {
                $media = $material->getFirstMedia('file');

                return Attachment::fromStorageDisk($media->disk, $media->getPathRelativeToRoot())
                    ->as($media->file_name)
                    ->withMime($media->mime_type);
            })
            ->all();
    }

    /**
     * Materials for the session's topic flagged for the 7-day reminder.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Material>
     */
    public function preparationMaterials()
    {
        if ($this->session->topic === null) {
            return new \Illuminate\Database\Eloquent\Collection;
        }

        return $this->session->topic->materials()
            ->where('is_preparatory', true)
            ->with('media')
            ->get();
    }

    /**
     * Uploaded preparation files that fit within the attachment size budget.
     *
     * @return Collection<int, Material>
     */
    private function attachableMaterials()
    {
        $budget = self::MAX_ATTACHMENT_BYTES;

        return $this->preparationMaterials()
            ->filter(fn (Material $material) => $material->getFirstMedia('file') !== null)
            ->filter(function (Material $material) use (&$budget): bool {
                $size = $material->getFirstMedia('file')->size;

                if ($size > $budget) {
                    return false;
                }

                $budget -= $size;

                return true;
            })
            ->values();
    }
}
