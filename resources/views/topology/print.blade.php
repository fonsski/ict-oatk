@php
    $W = 150; $H = 64;
    $icons = [
        'internet' => '🌐', 'router' => '📡', 'switch' => '🔀', 'server' => '🗄️',
        'workstation' => '💻', 'access_point' => '📶', 'printer' => '🖨️', 'other' => '🔧',
    ];
    $nodes = $diagram->nodes;
    $links = $diagram->links;

    // Границы схемы для viewBox (чтобы масштабировать под лист).
    if ($nodes->count()) {
        $minX = $nodes->min('pos_x') - 20;
        $minY = $nodes->min('pos_y') - 20;
        $maxX = $nodes->max(fn($n) => $n->pos_x) + $W + 20;
        $maxY = $nodes->max(fn($n) => $n->pos_y) + $H + 40;
    } else {
        $minX = 0; $minY = 0; $maxX = 800; $maxY = 400;
    }
    $vbW = max(1, $maxX - $minX);
    $vbH = max(1, $maxY - $minY);
    $centers = [];
    foreach ($nodes as $n) {
        $centers[$n->id] = ['x' => $n->pos_x + $W / 2, 'y' => $n->pos_y + $H / 2];
    }
@endphp
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <title>{{ $diagram->name }} — топология сети</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: -apple-system, Segoe UI, Roboto, sans-serif; margin: 0; color: #0f172a; }
        .bar { display: flex; align-items: center; justify-content: space-between; padding: 16px 24px; border-bottom: 1px solid #e2e8f0; }
        .bar h1 { font-size: 20px; margin: 0; }
        .bar .actions button, .bar .actions a { font-size: 14px; padding: 8px 16px; border: 1px solid #cbd5e1; border-radius: 6px; background: #fff; cursor: pointer; text-decoration: none; color: #0f172a; margin-left: 8px; }
        .bar .actions .primary { background: #2563eb; color: #fff; border-color: #2563eb; }
        .wrap { padding: 24px; }
        svg { width: 100%; height: auto; border: 1px solid #e2e8f0; }
        .legend { margin-top: 16px; font-size: 13px; color: #475569; }
        .legend span { display: inline-block; margin-right: 16px; }
        @media print {
            .bar { display: none; }
            .wrap { padding: 0; }
            svg { border: none; }
        }
    </style>
</head>
<body>
    <div class="bar">
        <h1>{{ $diagram->name }}</h1>
        <div class="actions">
            <a href="{{ route('topology.show', $diagram) }}">← Редактор</a>
            <button class="primary" onclick="window.print()">Печать</button>
        </div>
    </div>

    <div class="wrap">
        @if($diagram->description)
        <p style="color:#475569;margin:0 0 16px">{{ $diagram->description }}</p>
        @endif

        <svg viewBox="{{ $minX }} {{ $minY }} {{ $vbW }} {{ $vbH }}" xmlns="http://www.w3.org/2000/svg">
            {{-- Связи --}}
            @foreach($links as $link)
                @php $a = $centers[$link->source_id] ?? null; $b = $centers[$link->target_id] ?? null; @endphp
                @if($a && $b)
                <line x1="{{ $a['x'] }}" y1="{{ $a['y'] }}" x2="{{ $b['x'] }}" y2="{{ $b['y'] }}" stroke="#64748b" stroke-width="2"/>
                @if($link->label)
                <text x="{{ ($a['x'] + $b['x']) / 2 }}" y="{{ ($a['y'] + $b['y']) / 2 - 4 }}" font-size="11" fill="#475569" text-anchor="middle">{{ $link->label }}</text>
                @endif
                @endif
            @endforeach

            {{-- Узлы --}}
            @foreach($nodes as $node)
            <g transform="translate({{ $node->pos_x }},{{ $node->pos_y }})">
                <rect width="{{ $W }}" height="{{ $H }}" rx="8" fill="#ffffff" stroke="#94a3b8" stroke-width="1.5"/>
                <text x="12" y="30" font-size="22">{{ $icons[$node->type] ?? $icons['other'] }}</text>
                <text x="42" y="27" font-size="13" font-weight="600" fill="#0f172a">{{ \Illuminate\Support\Str::limit($node->label, 16) }}</text>
                @php $sub = $node->ip_address ?: ($node->room ? trim($node->room->number . ' ' . ($node->room->name ?? '')) : ''); @endphp
                @if($sub)
                <text x="42" y="46" font-size="11" fill="#64748b">{{ \Illuminate\Support\Str::limit($sub, 18) }}</text>
                @endif
            </g>
            @endforeach
        </svg>

        <div class="legend">
            @foreach($types as $value => $label)
            <span>{{ $icons[$value] ?? $icons['other'] }} {{ $label }}</span>
            @endforeach
        </div>
    </div>
</body>
</html>
