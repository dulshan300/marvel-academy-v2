<x-mail::message>
# Live lesson in two days

Hello {{ $child->parent->name }},

A quick reminder that {{ $child->first_name }}'s **{{ $session->course->key_stage->label() }} {{ $session->course->title }}** live lesson is on **{{ $session->starts_at->format('l j F') }} at {{ $session->starts_at->format('g:ia') }}**.

<x-mail::panel>
**{{ $session->title }}**<br>
Duration: {{ $session->duration_minutes }} minutes
@if ($session->teacher)
<br>Tutor: {{ $session->teacher->name }}
@endif
</x-mail::panel>

## How to join

1. {{ $child->first_name }} logs in to the student portal at {{ route('login') }} using their student login{{ $child->student ? ' ('.$child->student->email.')' : '' }}.
2. The **Join now** button appears on their dashboard 15 minutes before the lesson starts.
@if ($session->stream_url)
3. If needed, the lesson can also be opened directly: [join the live stream]({{ $session->stream_url }}).
@endif

<x-mail::button :url="route('student.sessions.show', $session)">
Go to the lesson page
</x-mail::button>

## Before the lesson

- Finish the question pack and bring any questions.
- Have a pen, paper and calculator ready.
- Test headphones or speakers a few minutes early.

Thanks,<br>
The Marvel Academy team
</x-mail::message>
