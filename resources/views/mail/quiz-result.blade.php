<x-mail::message>
# Quiz result for {{ $child->first_name }}

Hello {{ $parentName }},

{{ $child->first_name }} has just completed the **{{ $attempt->quiz->title }}** quiz in {{ $courseName }} ({{ $topicTitle }}).

<x-mail::panel>
**Score:** {{ $attempt->score }} / {{ $attempt->max_score }} ({{ $attempt->percentage }}%)<br>
**Result:** {{ $attempt->passed ? 'Passed' : 'Not yet passed' }} (pass mark {{ $attempt->quiz->pass_mark }}%)
</x-mail::panel>

@if ($attempt->passed)
Great work! Keep the momentum going in the next live session.
@else
This topic is worth another look. Your child can review the explanations for each question and retake the quiz from their student portal.
@endif

<x-mail::button :url="route('parent.dashboard')">
View progress
</x-mail::button>

Thanks,<br>
The Marvel Academy team
</x-mail::message>
