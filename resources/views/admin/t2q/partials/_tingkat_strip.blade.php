@if ($tingkatTabs !== [])
    <div class="t2q-tingkat-strip mb-3">
        <div class="t2q-tingkat-tabs" role="tablist">
            @foreach ($tingkatTabs as $tingkatKey => $studentCount)
                @php
                    $isTabActive = strcasecmp($selectedTingkat, (string) $tingkatKey) === 0;
                    $tabQuery = array_filter([
                        ...($tabBaseQuery ?? []),
                        'tingkat' => $tingkatKey,
                    ]);
                @endphp
                <a href="{{ route($tabRoute, $tabQuery) }}"
                    class="t2q-tingkat-tab {{ $isTabActive ? 'is-active' : '' }}" role="tab"
                    aria-selected="{{ $isTabActive ? 'true' : 'false' }}">
                    <span>Tingkat {{ $tingkatKey }}</span>
                    <span class="t2q-tingkat-count">{{ $studentCount }}</span>
                </a>
            @endforeach
        </div>

        @if ($selectedTingkat !== '' && $targetCapaian->isNotEmpty())
            <div class="t2q-target-strip">
                @foreach ($targetCapaian as $targetItem)
                    <span class="t2q-target-pill">
                        <i class="fa fa-bullseye"></i>
                        <span>Target: {{ $targetItem->ringkasan_target }}</span>
                    </span>
                @endforeach
            </div>
        @endif
    </div>
@endif

