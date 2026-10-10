<x-filament-widgets::widget>
    <x-filament::section heading="Aktivitas Menunggu Persetujuan" icon="heroicon-o-bell-alert">
        <div class="eco-report-grid eco-report-grid-4">
            @foreach ($groups as $group)
                <div class="eco-report-card">
                    <h3>{{ $group['label'] }} · {{ $group['count'] }}</h3>
                    @forelse ($group['items'] as $item)
                        <a href="{{ $item['url'] }}" class="eco-report-row" style="text-decoration:none">
                            <b style="text-align:left">{{ $item['title'] }}</b>
                            <span>{{ $item['meta'] }}</span>
                        </a>
                    @empty
                        <div class="eco-report-row"><span>Tidak ada yang menunggu.</span></div>
                    @endforelse
                    @if ($group['count'] > 0)
                        <div style="margin-top:.75rem">
                            <x-filament::link :href="$group['url']" icon="heroicon-m-arrow-right" icon-position="after">Proses sekarang</x-filament::link>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
