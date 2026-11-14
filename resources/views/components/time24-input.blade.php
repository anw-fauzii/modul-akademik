@props([
    'compact' => false,
    'wrapperClass' => '',
])

<div class="time24-field {{ $compact ? 'time24-field--compact' : '' }} {{ $wrapperClass }}">
    <input
        {{ $attributes->merge([
            'type' => 'text',
            'data-time24' => 'true',
            'inputmode' => 'numeric',
            'maxlength' => '5',
            'autocomplete' => 'off',
            'placeholder' => 'HH:MM',
            'pattern' => '^([01]\\d|2[0-3]):[0-5]\\d$',
        ])->class(['time24-input']) }}
    >
    <button type="button" class="time24-trigger" tabindex="-1"
        aria-label="Pilih jam format 24 jam" title="Pilih Jam (Format 24 Jam)">
        <i class="fa fa-clock"></i>
    </button>
</div>

