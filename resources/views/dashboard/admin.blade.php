<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Dashboard
            </h2>
            <div class="flex gap-2">
                <button onclick="refreshStats()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm">
                    <i class="fas fa-sync-alt mr-2"></i>Refresh
                </button>
                <form action="{{ route('dashboard.clear-cache') }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition text-sm">
                        <i class="fas fa-trash mr-2"></i>Clear Cache
                    </button>
                </form>
            </div>
        </div>
    </x-slot>

    

    @push('scripts')
    <script>
        function refreshStats() {
            // Show loading state
            const btn = event.target.closest('button');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Loading...';
            
            // Reload page after a short delay
            setTimeout(() => {
                window.location.reload();
            }, 500);
        }
    </script>
    @endpush
</x-app-layout>