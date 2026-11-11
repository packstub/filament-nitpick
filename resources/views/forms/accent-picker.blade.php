@php
    $statePath = $getStatePath();
    $swatches = $getSwatches();
    $isDisabled = $isDisabled();
@endphp

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div
        x-data="{ state: $wire.$entangle('{{ $statePath }}', true) }"
        class="nitpick-accents"
        role="radiogroup"
    >
        <label class="nitpick-accent nitpick-accent-theme" x-bind:class="{ 'nitpick-accent-active': ! state }" title="{{ __('nitpick::themes.accent.theme') }}">
            <input type="radio" name="{{ $statePath }}" value="" x-model="state" @disabled($isDisabled) class="nitpick-option-input" />
            <span class="nitpick-accent-label">{{ __('nitpick::themes.accent.theme') }}</span>
        </label>

        @foreach ($swatches as $name => $color)
            <label class="nitpick-accent" x-bind:class="{ 'nitpick-accent-active': state === @js($name) }" title="{{ str($name)->headline() }}">
                <input type="radio" name="{{ $statePath }}" value="{{ $name }}" x-model="state" @disabled($isDisabled) class="nitpick-option-input" />
                <span class="nitpick-accent-swatch" style="background: {{ $color }}"></span>
                <span class="sr-only">{{ str($name)->headline() }}</span>
            </label>
        @endforeach

        <label
            class="nitpick-accent nitpick-accent-custom"
            x-bind:class="{ 'nitpick-accent-active': state && state.charAt(0) === '#' }"
            title="{{ __('nitpick::themes.accent.custom') }}"
        >
            <input
                type="color"
                x-bind:value="state && state.charAt(0) === '#' ? state : '#4f46e5'"
                x-on:change="state = $event.target.value"
                @disabled($isDisabled)
                class="nitpick-accent-color-input"
            />
            <span class="nitpick-accent-swatch nitpick-accent-swatch-custom" x-bind:style="state && state.charAt(0) === '#' ? 'background: ' + state : ''"></span>
            <span class="sr-only">{{ __('nitpick::themes.accent.custom') }}</span>
        </label>
    </div>
</x-dynamic-component>
