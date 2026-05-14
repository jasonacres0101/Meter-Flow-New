<x-layouts.app title="Add User">
    <h1 class="app-page-title mb-6">Add User</h1>
    <form method="post" action="{{ route('users.store') }}" class="app-panel rounded-xl p-5">@include('users._form')</form>
</x-layouts.app>
