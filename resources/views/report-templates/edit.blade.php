<x-layouts.app title="Edit Template">
    <h1 class="app-page-title mb-6">Edit Template</h1>
    <form method="post" action="{{ route('report-templates.update', $reportTemplate) }}">@method('PUT')@include('report-templates._form')</form>
</x-layouts.app>
