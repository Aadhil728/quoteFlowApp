@props([
    'name',
    'options' => [],
    'value' => null,
    'label' => null,
    'placeholder' => 'Select an option',
    'searchPlaceholder' => 'Search options...',
    'helper' => null,
    'emptyMessage' => 'No matching options found.',
    'required' => false,
])
@php
    $controlId = 'searchable-select-'.Illuminate\Support\Str::slug($name).'-'.Illuminate\Support\Str::lower(Illuminate\Support\Str::random(6));
    $selected = collect($options)->firstWhere('value', (string) $value);
@endphp
<div class="searchable-select-field" data-searchable-select>
    @if($label)<label id="{{ $controlId }}-label" for="{{ $controlId }}-search">{{ $label }}</label>@endif
    <div class="searchable-select-control">
        <input type="hidden" name="{{ $name }}" value="{{ $selected['value'] ?? $value }}" data-select-value @required($required)>
        <button class="searchable-select-trigger" type="button" aria-haspopup="listbox" aria-expanded="false" aria-labelledby="{{ $controlId }}-label {{ $controlId }}-selection" data-select-trigger>
            <span class="searchable-select-code" data-selected-code>{{ $selected['code'] ?? '—' }}</span>
            <span class="searchable-select-summary" id="{{ $controlId }}-selection">
                <strong data-selected-label>{{ $selected['label'] ?? $placeholder }}</strong>
                <small data-selected-meta>{{ $selected['meta'] ?? '' }}</small>
            </span>
            <x-icon name="chevron" size="16" class="searchable-select-chevron" />
        </button>
        <div class="searchable-select-popover" data-select-popover hidden>
            <div class="searchable-select-search">
                <x-icon name="search" size="16" />
                <input id="{{ $controlId }}-search" type="search" placeholder="{{ $searchPlaceholder }}" autocomplete="off" aria-controls="{{ $controlId }}-options" data-select-search>
            </div>
            <div class="searchable-select-options" id="{{ $controlId }}-options" role="listbox" aria-labelledby="{{ $controlId }}-label" data-select-options>
                @foreach($options as $option)
                    <button type="button" role="option" aria-selected="{{ ($selected['value'] ?? null) === $option['value'] ? 'true' : 'false' }}" tabindex="-1" data-select-option data-value="{{ $option['value'] }}" data-code="{{ $option['code'] ?? $option['value'] }}" data-label="{{ $option['label'] }}" data-meta="{{ $option['meta'] ?? '' }}" data-search="{{ Illuminate\Support\Str::lower($option['search'] ?? implode(' ', $option)) }}">
                        <span class="searchable-select-option-code">{{ $option['code'] ?? $option['value'] }}</span>
                        <span class="searchable-select-option-label">{{ $option['label'] }}</span>
                        <small>{{ $option['meta'] ?? '' }}</small>
                        <x-icon name="check" size="15" />
                    </button>
                @endforeach
            </div>
            <p class="searchable-select-empty" data-select-empty hidden>{{ $emptyMessage }}</p>
        </div>
    </div>
    @if($helper)<small class="searchable-select-helper">{{ $helper }}</small>@endif
    @error($name)<span class="field-error">{{ $message }}</span>@enderror
</div>
