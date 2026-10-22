<x-layouts::app :title="$portal.' dashboard'">
    <section class="container-page py-20">
        <h1 class="text-3xl font-extrabold text-brand-950">{{ $portal }} dashboard</h1>
        <p class="mt-3 text-slate-600">Signed in as {{ auth()->user()->name }}.</p>
        <form method="POST" action="{{ route('logout') }}" class="mt-8">
            @csrf
            <button type="submit" class="btn-secondary">Log out</button>
        </form>
    </section>
</x-layouts::app>
