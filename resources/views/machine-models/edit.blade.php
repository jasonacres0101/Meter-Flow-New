<x-layouts.app title="Edit Model">
    <h1 class="app-page-title mb-6">Edit Model</h1>
    <form method="post" action="{{ route('machine-models.update', $machineModel) }}" class="app-panel rounded-xl p-5">@method('PUT')@include('machine-models._form')</form>
</x-layouts.app>
