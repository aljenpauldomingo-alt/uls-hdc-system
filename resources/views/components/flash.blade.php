@if (session('status'))
    <div class="rounded-lg border border-green-500/50 bg-green-600 px-4 py-3 text-sm font-medium text-white shadow-lg backdrop-blur-sm dark:bg-green-500/80" role="status">
        {{ session('status') }}
    </div>
@endif
@if (session('error'))
    <div class="rounded-lg border border-red-500/50 bg-red-600 px-4 py-3 text-sm font-medium text-white shadow-lg backdrop-blur-sm dark:bg-red-500/80" role="alert">
        {{ session('error') }}
    </div>
@endif
