{{-- resources/views/components/account-badge.blade.php --}}
@props([
    'account' => null,
    'confidence' => 0,
    'isManual' => false,
    'showConfidence' => true,
    'size' => 'md' // sm, md, lg
])

@php
    $sizeClasses = [
        'sm' => 'px-2 py-1 text-xs',
        'md' => 'px-3 py-1.5 text-sm',
        'lg' => 'px-4 py-2 text-base'
    ];
    
    $confidenceColor = match(true) {
        $confidence >= 90 => 'bg-green-100 text-green-800 border-green-200',
        $confidence >= 70 => 'bg-blue-100 text-blue-800 border-blue-200',
        $confidence >= 50 => 'bg-yellow-100 text-yellow-800 border-yellow-200',
        default => 'bg-gray-100 text-gray-800 border-gray-200'
    };
@endphp

@if($account)
    <div class="inline-flex items-center gap-2 {{ $sizeClasses[$size] }} rounded-lg border {{ $confidenceColor }} font-medium">
        {{-- Account Icon --}}
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
        </svg>

        {{-- Account Info --}}
        <div class="flex flex-col leading-tight">
            <span class="font-semibold">{{ $account->name }}</span>
            @if($account->code)
                <span class="text-xs opacity-75">{{ $account->code }}</span>
            @endif
        </div>

        {{-- Confidence Score --}}
        @if($showConfidence && $confidence > 0)
            <div class="flex items-center gap-1 px-2 py-0.5 rounded-md bg-white/50">
                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                </svg>
                <span class="text-xs font-bold">{{ $confidence }}%</span>
            </div>
        @endif

        {{-- Manual Badge --}}
        @if($isManual)
            <span class="px-2 py-0.5 text-xs font-semibold rounded-md bg-purple-100 text-purple-800">
                Manual
            </span>
        @endif
    </div>
@else
    <div class="inline-flex items-center gap-2 {{ $sizeClasses[$size] }} rounded-lg border border-gray-300 bg-gray-50 text-gray-500">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
        <span>No Account</span>
    </div>
@endif