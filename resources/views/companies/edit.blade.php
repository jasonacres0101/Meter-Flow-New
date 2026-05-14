<x-layouts.app title="Edit Company">
    <h1 class="app-page-title mb-6">Edit Company</h1>
    <form method="post" action="{{ route('companies.update', $company) }}" class="app-panel rounded-xl p-5">@method('PUT')@include('companies._form')</form>
</x-layouts.app>
