<x-layouts.app title="Add Model">
    <h1 class="app-page-title mb-6">Add Model</h1>
    <form method="post" action="{{ route('machine-models.store') }}" class="app-panel rounded-xl p-5">@include('machine-models._form')</form>
</x-layouts.app>
