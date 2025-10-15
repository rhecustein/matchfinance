{{-- 
    Confirm Dialog Component
    Path: resources/views/components/confirm-dialog.blade.php
--}}

<div x-data="confirmDialog()" 
     @open-confirm.window="open($event.detail)"
     x-show="show" 
     x-cloak
     class="fixed inset-0 z-50 overflow-y-auto"
     style="display: none;">
    
    {{-- Backdrop --}}
    <div x-show="show" 
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="close()"
         class="fixed inset-0 bg-black/60 backdrop-blur-sm transition-opacity">
    </div>

    {{-- Dialog --}}
    <div class="flex min-h-full items-center justify-center p-4">
        <div x-show="show"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             @click.away="close()"
             class="relative w-full max-w-lg transform overflow-hidden rounded-2xl bg-gradient-to-br from-slate-800 to-slate-900 border border-slate-700 shadow-2xl transition-all">
            
            {{-- Content --}}
            <div class="p-6">
                {{-- Icon --}}
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full mb-4"
                     :class="{
                         'bg-yellow-600/20': type === 'warning',
                         'bg-red-600/20': type === 'danger',
                         'bg-green-600/20': type === 'success',
                         'bg-blue-600/20': type === 'info'
                     }">
                    <i :class="getIconClass()" class="text-4xl"></i>
                </div>

                {{-- Title --}}
                <h3 class="text-xl font-bold text-white text-center mb-2" x-text="title"></h3>

                {{-- Message --}}
                <p class="text-sm text-gray-400 text-center mb-6" x-text="message"></p>

                {{-- Actions --}}
                <div class="flex gap-3">
                    {{-- Cancel Button --}}
                    <button @click="close()"
                            type="button"
                            class="flex-1 px-4 py-3 bg-slate-700 hover:bg-slate-600 text-white font-semibold rounded-lg transition focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2 focus:ring-offset-slate-900">
                        <span x-text="cancelText"></span>
                    </button>

                    {{-- Confirm Button --}}
                    <button @click="confirm()"
                            type="button"
                            :class="getConfirmButtonClass()"
                            class="flex-1 px-4 py-3 text-white font-semibold rounded-lg transition focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900">
                        <span x-text="confirmText"></span>
                    </button>
                </div>
            </div>

            {{-- Close button (top right) --}}
            <button @click="close()"
                    type="button"
                    class="absolute top-4 right-4 text-gray-400 hover:text-white transition">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
    </div>
</div>