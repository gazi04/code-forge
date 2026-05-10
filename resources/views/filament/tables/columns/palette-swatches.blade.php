{{-- resources/views/filament/tables/columns/palette-swatches.blade.php --}}
{{-- Renders three small colour circles for the primary, secondary, and accent
     palette values stored in the theme pack's config JSON column. --}}

@php
    $palette = $getState() ?? [];
    $swatches = [
        'primary'   => $palette['primary']   ?? null,
        'secondary' => $palette['secondary'] ?? null,
        'accent'    => $palette['accent']    ?? null,
    ];
@endphp

<div class="flex items-center gap-1.5">
    @foreach ($swatches as $label => $color)
        @if ($color)
            <div
                title="{{ ucfirst($label) }}: {{ $color }}"
                class="h-5 w-5 rounded-full border border-white/20 shadow-sm ring-1 ring-black/10"
                style="background-color: {{ $color }};"
            ></div>
        @else
            <div
                title="{{ ucfirst($label) }}: not set"
                class="h-5 w-5 rounded-full border border-dashed border-gray-300 bg-gray-100"
            ></div>
        @endif
    @endforeach
</div>
