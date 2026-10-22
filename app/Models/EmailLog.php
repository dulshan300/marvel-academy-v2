<?php

namespace App\Models;

use Database\Factories\EmailLogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['mailable', 'subject', 'recipients', 'message_id', 'sent_at'])]
class EmailLog extends Model
{
    /** @use HasFactory<EmailLogFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
        ];
    }

    /**
     * The mailable or notification class without its namespace.
     */
    public function typeLabel(): string
    {
        return $this->mailable ? str(class_basename($this->mailable))->headline()->toString() : 'Other';
    }
}
