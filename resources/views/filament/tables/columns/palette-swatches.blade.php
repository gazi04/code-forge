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

<div style="display: flex; align-items: center; gap: 6px;">
    @foreach ($swatches as $label => $color)
        @if ($color)
            <div
                title="{{ ucfirst($label) }}: {{ $color }}"
                style="width: 20px; height: 20px; border-radius: 9999px; border: 1px solid rgba(255, 255, 255, 0.2); box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1); background-color: {{ $color }};"
            ></div>
        @else
            <div
                title="{{ ucfirst($label) }}: not set"
                style="width: 20px; height: 20px; border-radius: 9999px; border: 1px dashed #d1d5db; background: #f3f4f6;"
            ></div>
        @endif
    @endforeach
</div>
