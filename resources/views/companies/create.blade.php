<x-layouts.app title="Add Company">
    <h1 class="app-page-title mb-6">Add Company</h1>
    <form method="post" action="{{ route('companies.store') }}" class="app-panel rounded-xl p-5">@include('companies._form')</form>
</x-layouts.app>
