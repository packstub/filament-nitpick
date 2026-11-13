@php
    use Filament\Support\View\ComponentAttributeBag;

    $options = $getOptions();
    $statePath = $getStatePath();
    $isDisabled = $isDisabled();
    $hasDescriptions = $hasDescriptions();
    $swatches = $hasSwatches() ? array_map(fn (array $option): array => array_slice($option['theme']->getSwatches(), 0, 3), $options) : [];
    // No theme with known colours: leave the row out instead of a blank strip on every card.
    $hasSwatches = filled(array_filter($swatches));
    $shouldPersist = $shouldPersist();
@endphp

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div
        x-data="{ state: $wire.$entangle('{{ $statePath }}', true) }"
        {{ ($hasCustomColumns() ? (new ComponentAttributeBag)->grid($getColumns()) : new ComponentAttributeBag)->class(['nitpick-picker']) }}
        role="radiogroup"
    >
        @foreach ($options as $index => $option)
            @php
                $value = $option['value'];
                $theme = $option['theme'];
                $description = $option['hint'] === null ? $theme->getDescription() : null;
            @endphp

            <label
                class="nitpick-option"
                x-bind:class="{ 'nitpick-option-active': state === @js($value) }"
                @if (! $hasDescriptions && filled($description)) title="{{ $description }}" @endif
            >
                <input
                    type="radio"
                    name="{{ $statePath }}"
                    value="{{ $value }}"
                    x-model="state"
                    x-on:change="window.Nitpick && window.Nitpick.pick(@js($theme->getId()), { reload: @js($shouldPersist) })"
                    @disabled($isDisabled)
                    class="nitpick-option-input"
                />

                @if ($hasSwatches)
                    {{-- Colours unknown (set in a plugin's boot(), say): the row stays empty, so the names line up. --}}
                    <span class="nitpick-swatches" aria-hidden="true">
                        @foreach ($swatches[$index] as $swatch)
                            <span class="nitpick-swatch" style="background: {{ $swatch }}"></span>
                        @endforeach
                    </span>
                @endif

                <span class="nitpick-option-label">
                    {{ $option['label'] }}
                    <span class="nitpick-option-current" x-show="state === @js($value)" x-cloak>
                        {{ __('nitpick::themes.picker.current') }}
                    </span>
                </span>

                @if (filled($option['hint']))
                    <span class="nitpick-option-hint">{{ $option['hint'] }}</span>
                @endif

                @if ($hasDescriptions && filled($description))
                    <span class="nitpick-option-description">{{ $description }}</span>
                @endif
            </label>
        @endforeach
    </div>
</x-dynamic-component>
