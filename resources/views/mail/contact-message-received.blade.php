<x-mail::message>
# New website enquiry

**From:** {{ $contactMessage->name }} ({{ $contactMessage->email }})
@if ($contactMessage->phone)
**Phone:** {{ $contactMessage->phone }}
@endif
**Subject:** {{ $contactMessage->subject }}

<x-mail::panel>
{{ $contactMessage->message }}
</x-mail::panel>

Reply to this email to respond directly.
</x-mail::message>
