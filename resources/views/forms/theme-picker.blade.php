@php
    $themes = $getThemes();
    $statePath = $getStatePath();
    $isDisabled = $isDisabled();
    $hasDescriptions = $hasDescriptions();
    $hasSwatches = $hasSwatches();
@endphp

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div
        x-data="{ state: $wire.$entangle('{{ $statePath }}', true) }"
        class="pst-theme-picker"
        role="radiogroup"
    >
        @foreach ($themes as $id => $theme)
            @php
                $description = $theme->getDescription();
            @endphp

            <label
                class="pst-theme-option"
                x-bind:class="{ 'pst-theme-option-active': state === @js($id) }"
                @if (! $hasDescriptions && filled($description)) title="{{ $description }}" @endif
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

                @if ($hasSwatches)
                    <span class="pst-theme-swatches" aria-hidden="true">
                        @forelse (array_slice($theme->getSwatches(), 0, 3) as $swatch)
                            <span class="pst-theme-swatch" style="background: {{ $swatch }}"></span>
                        @empty
                            {{-- Colours unknown (set in a plugin's boot(), say): empty swatches keep the cards in line. --}}
                            @for ($i = 0; $i < 3; $i++)
                                <span class="pst-theme-swatch pst-theme-swatch-empty"></span>
                            @endfor
                        @endforelse
                    </span>
                @endif

                <span class="pst-theme-option-label">
                    {{ $theme->getLabel() }}
                    <span class="pst-theme-option-current" x-show="state === @js($id)" x-cloak>
                        {{ __('nitpick::themes.picker.current') }}
                    </span>
                </span>

                @if ($hasDescriptions && filled($description))
                    <span class="pst-theme-option-description">{{ $description }}</span>
                @endif
            </label>
        @endforeach
    </div>
</x-dynamic-component>
