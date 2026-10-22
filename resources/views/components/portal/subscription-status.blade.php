@props(['subscription'])

@if ($subscription === null)
    <x-portal.badge tone="slate">No plan</x-portal.badge>
@elseif ($subscription->onGracePeriod())
    <x-portal.badge tone="amber">Ends {{ $subscription->ends_at->format('j M Y') }}</x-portal.badge>
@elseif ($subscription->onTrial())
    <x-portal.badge tone="brand">Free trial until {{ $subscription->trial_ends_at->format('j M') }}</x-portal.badge>
@elseif ($subscription->pastDue() || $subscription->hasIncompletePayment())
    <x-portal.badge tone="red">Payment needed</x-portal.badge>
@elseif ($subscription->valid())
    <x-portal.badge tone="green">Active</x-portal.badge>
@else
    <x-portal.badge tone="slate">Ended</x-portal.badge>
@endif
