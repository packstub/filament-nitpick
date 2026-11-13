@php
    use Filament\Support\View\ComponentAttributeBag;

    $themes = $getThemes();
    $statePath = $getStatePath();
    $isDisabled = $isDisabled();
    $hasDescriptions = $hasDescriptions();
    $swatches = $hasSwatches() ? array_map(fn ($theme): array => array_slice($theme->getSwatches(), 0, 3), $themes) : [];
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
        @foreach ($themes as $id => $theme)
            @php
                $description = $theme->getDescription();
            @endphp

            <label
                class="nitpick-option"
                x-bind:class="{ 'nitpick-option-active': state === @js($id) }"
                @if (! $hasDescriptions && filled($description)) title="{{ $description }}" @endif
            >
                <input
                    type="radio"
                    name="{{ $statePath }}"
                    value="{{ $id }}"
                    x-model="state"
                    x-on:change="window.Nitpick && window.Nitpick.pick(@js($id), { reload: @js($shouldPersist) })"
                    @disabled($isDisabled)
                    class="nitpick-option-input"
                />

                @if ($hasSwatches)
                    {{-- Colours unknown (set in a plugin's boot(), say): the row stays empty, so the names line up. --}}
                    <span class="nitpick-swatches" aria-hidden="true">
                        @foreach ($swatches[$id] as $swatch)
                            <span class="nitpick-swatch" style="background: {{ $swatch }}"></span>
                        @endforeach
                    </span>
                @endif

                <span class="nitpick-option-label">
                    {{ $theme->getLabel() }}
                    <span class="nitpick-option-current" x-show="state === @js($id)" x-cloak>
                        {{ __('nitpick::themes.picker.current') }}
                    </span>
                </span>

                @if ($hasDescriptions && filled($description))
                    <span class="nitpick-option-description">{{ $description }}</span>
                @endif
            </label>
        @endforeach
    </div>
</x-dynamic-component>
