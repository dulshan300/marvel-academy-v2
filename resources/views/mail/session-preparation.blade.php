<x-mail::message>
# {{ $child->first_name }}'s next live lesson is in one week

Hello {{ $child->parent->name }},

{{ $child->first_name }} has a live **{{ $session->course->key_stage->label() }} {{ $session->course->title }}** lesson coming up:

<x-mail::panel>
**{{ $session->title }}**<br>
{{ $session->starts_at->format('l j F Y') }} at {{ $session->starts_at->format('g:ia') }} ({{ $session->duration_minutes }} minutes)@if ($session->teacher)<br>
Tutor: {{ $session->teacher->name }}@endif
</x-mail::panel>

## How to prepare

Our lessons start with past-paper questions, so the best preparation is to work through the materials below before the lesson and note anything that feels tricky.

@if ($materials->isNotEmpty())
@foreach ($materials as $material)
- **{{ $material->title }}**@if (in_array($material->id, $attachedIds, true)) (attached)@else ([open in the portal]({{ route('materials.download', $material) }}))@endif
@endforeach
@else
- Course notes and question packs are available in the student portal.
@endif

<x-mail::button :url="route('student.courses.show', $session->course)">
Open the course
</x-mail::button>

We will send joining instructions two days before the lesson.

Thanks,<br>
The Marvel Academy team
</x-mail::message>
