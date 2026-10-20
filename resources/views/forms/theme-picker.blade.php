@php
    $themes = $getThemes();
    $statePath = $getStatePath();
    $isDisabled = $isDisabled();
@endphp

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div
        x-data="{ state: $wire.$entangle('{{ $statePath }}', true) }"
        class="pst-theme-picker"
        role="radiogroup"
    >
        @foreach ($themes as $id => $theme)
            <label
                class="pst-theme-option"
                x-bind:class="{ 'pst-theme-option-active': state === @js($id) }"
            >
                <input
                    type="radio"
                    name="{{ $statePath }}"
                    value="{{ $id }}"
                    x-model="state"
                    x-on:change="window.PackstubThemes && window.PackstubThemes.preview(@js($id))"
                    @disabled($isDisabled)
                    class="pst-theme-option-input"
                />

                <span class="pst-theme-swatches" aria-hidden="true">
                    @foreach (array_slice($theme->getSwatches(), 0, 3) as $swatch)
                        <span class="pst-theme-swatch" style="background: {{ $swatch }}"></span>
                    @endforeach
                </span>

                <span class="pst-theme-option-label">
                    {{ $theme->getLabel() }}
                    <span class="pst-theme-option-current" x-show="state === @js($id)" x-cloak>
                        {{ __('nitpick::themes.picker.current') }}
                    </span>
                </span>

                @if (filled($description = $theme->getDescription()))
                    <span class="pst-theme-option-description">{{ $description }}</span>
                @endif
            </label>
        @endforeach
    </div>
</x-dynamic-component>
