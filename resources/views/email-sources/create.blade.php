<x-layouts.app title="Add Email Source">
    <h1 class="mb-6 text-2xl font-black">Add Email Source</h1>
    <form method="post" action="{{ route('email-sources.store') }}">@include('email-sources._form')</form>
</x-layouts.app>
