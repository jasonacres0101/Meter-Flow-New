<x-layouts.app title="Add Machine">
    <h1 class="app-page-title mb-6">Add Machine</h1>
    <form method="post" action="{{ route('machines.store') }}">@include('machines._form')</form>
</x-layouts.app>
