<x-layouts.app title="Edit User">
    <h1 class="app-page-title mb-6">Edit User</h1>
    <form method="post" action="{{ route('users.update', $managedUser) }}" class="app-panel rounded-xl p-5">@method('PUT')@include('users._form')</form>
</x-layouts.app>
