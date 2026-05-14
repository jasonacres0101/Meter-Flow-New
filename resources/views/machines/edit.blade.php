<x-layouts.app title="Edit Machine">
    <h1 class="app-page-title mb-6">Edit Machine</h1>
    <form method="post" action="{{ route('machines.update', $machine) }}">@method('PUT')@include('machines._form')</form>
</x-layouts.app>
