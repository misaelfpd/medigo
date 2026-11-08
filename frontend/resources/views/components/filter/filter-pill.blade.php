@props([
    'active' => false,
    'icon' => null,
])

<button {{ $attributes->merge(['type' => 'button', 'class' => 'chip-filter text-nowrap' . ($active ? ' active' : '')]) }}>
    @if($icon)
        <i class="{{ $icon }} me-1"></i>
    @endif
    {{ $slot }}
</button>
