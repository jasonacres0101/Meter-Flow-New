<x-layouts.app title="Edit Email Source">
    <h1 class="mb-6 text-2xl font-black">Edit Email Source</h1>
    <form method="post" action="{{ route('email-sources.update', $emailSource) }}">@method('PUT')@include('email-sources._form')</form>
</x-layouts.app>
