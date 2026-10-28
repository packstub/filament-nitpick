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
        {{ ($hasCustomColumns() ? (new ComponentAttributeBag)->grid($getColumns()) : new ComponentAttributeBag)->class(['pst-theme-picker']) }}
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
                    x-on:change="window.PackstubThemes && window.PackstubThemes.pick(@js($id), { reload: @js($shouldPersist) })"
                    @disabled($isDisabled)
                    class="pst-theme-option-input"
                />

                @if ($hasSwatches)
                    {{-- Colours unknown (set in a plugin's boot(), say): the row stays empty, so the names line up. --}}
                    <span class="pst-theme-swatches" aria-hidden="true">
                        @foreach ($swatches[$id] as $swatch)
                            <span class="pst-theme-swatch" style="background: {{ $swatch }}"></span>
                        @endforeach
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
