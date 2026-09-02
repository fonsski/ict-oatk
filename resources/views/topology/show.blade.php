@extends('layouts.app')

@section('title', $diagram->name . ' - Топология сети')

@section('content')
<div class="container-width section-padding">
    <!-- Заголовок -->
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div>
            <a href="{{ route('topology.index') }}" class="text-sm text-slate-500 hover:text-slate-700">← Все схемы</a>
            <h1 class="text-2xl font-bold text-slate-900">{{ $diagram->name }}</h1>
            @if($diagram->description)
            <p class="text-slate-600 text-sm">{{ $diagram->description }}</p>
            @endif
        </div>
        <div class="flex items-center gap-3">
            <span id="topology-status" class="text-sm text-slate-400"></span>
            <a href="{{ route('topology.print', $diagram) }}" target="_blank" class="btn-secondary">Печать</a>
        </div>
    </div>

    <div class="flex flex-col lg:flex-row gap-4">
        <!-- Холст и панель инструментов -->
        <div class="flex-1 min-w-0">
            <div class="card p-3 mb-3 flex flex-wrap items-center gap-3">
                <select id="node-type" class="form-input py-1.5 text-sm w-auto">
                    @foreach($types as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
                <button type="button" id="add-node-btn" class="btn-primary py-1.5 text-sm">Добавить узел</button>
                <button type="button" id="connect-btn" class="btn-secondary py-1.5 text-sm">Режим соединения</button>

                <div class="flex items-center gap-1 ml-auto border border-slate-200 rounded-lg px-1 py-1">
                    <button type="button" id="zoom-out" title="Уменьшить масштаб области" class="w-7 h-7 flex items-center justify-center text-slate-600 hover:bg-slate-100 rounded">−</button>
                    <span id="zoom-label" class="text-xs text-slate-500 w-10 text-center select-none">100%</span>
                    <button type="button" id="zoom-in" title="Увеличить масштаб области" class="w-7 h-7 flex items-center justify-center text-slate-600 hover:bg-slate-100 rounded">+</button>
                    <button type="button" id="zoom-reset" title="Сбросить масштаб" class="ml-1 text-xs text-slate-500 hover:text-slate-700 px-1.5">100%</button>
                </div>

                <span class="text-sm text-slate-500 w-full">Перетаскивайте узлы мышью. Клик по узлу — свойства. Ctrl/⌘ + колесо мыши над схемой — масштаб области.</span>
            </div>

            <div id="canvas-scroll" class="card p-0 overflow-auto" style="max-height:70vh">
                <svg id="topology-canvas" width="2000" height="1400" class="bg-slate-50 select-none" style="transform-origin:0 0">
                    <g id="links-layer"></g>
                    <g id="nodes-layer"></g>
                </svg>
            </div>
        </div>

        <!-- Панель свойств узла -->
        <div id="node-panel" class="card p-5 w-full lg:w-80 flex-shrink-0 hidden">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-slate-900">Узел</h3>
                <button type="button" id="panel-close" class="text-slate-400 hover:text-slate-600">✕</button>
            </div>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Подпись</label>
                    <input type="text" id="panel-label" maxlength="255" class="form-input">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Тип</label>
                    <select id="panel-type" class="form-input">
                        @foreach($types as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">IP-адрес</label>
                    <input type="text" id="panel-ip" maxlength="45" placeholder="192.168.1.1" class="form-input">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Кабинет</label>
                    <select id="panel-room" class="form-input">
                        <option value="">Не указан</option>
                        @foreach($rooms as $room)
                        <option value="{{ $room->id }}">{{ $room->display_label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Масштаб узла</label>
                    <select id="panel-scale" class="form-input">
                        <option value="0.75">75%</option>
                        <option value="1" selected>100%</option>
                        <option value="1.25">125%</option>
                        <option value="1.5">150%</option>
                        <option value="2">200%</option>
                    </select>
                    <p class="text-xs text-slate-500 mt-1">Размер этой карточки на схеме, независимо от масштаба всей области.</p>
                </div>
                <div class="flex items-center justify-between pt-2">
                    <button type="button" id="panel-save" class="btn-primary py-1.5 text-sm">Сохранить</button>
                    <button type="button" id="panel-delete" class="text-sm font-medium text-red-600 hover:text-red-800">Удалить узел</button>
                </div>
            </div>
        </div>
    </div>
</div>

@php
    $nodesData = $diagram->nodes->map(fn($n) => [
        'id' => $n->id, 'label' => $n->label, 'type' => $n->type,
        'ip_address' => $n->ip_address, 'room_id' => $n->room_id,
        'room_label' => $n->room ? $n->room->display_label : null,
        'pos_x' => $n->pos_x, 'pos_y' => $n->pos_y, 'scale' => (float) $n->scale,
    ])->values();
    $linksData = $diagram->links->map(fn($l) => [
        'id' => $l->id, 'source_id' => $l->source_id, 'target_id' => $l->target_id, 'label' => $l->label,
    ])->values();
@endphp
<script>
    window.topologyConfig = {
        csrf: '{{ csrf_token() }}',
        diagramId: {{ $diagram->id }},
        types: @json($types),
        nodes: @json($nodesData),
        links: @json($linksData),
        urls: {
            nodeStore: '{{ route('topology.nodes.store', $diagram) }}',
            nodeUpdate: '{{ route('topology.nodes.update', ['topology' => $diagram->id, 'node' => '__ID__']) }}',
            nodeDestroy: '{{ route('topology.nodes.destroy', ['topology' => $diagram->id, 'node' => '__ID__']) }}',
            linkStore: '{{ route('topology.links.store', $diagram) }}',
            linkDestroy: '{{ route('topology.links.destroy', ['topology' => $diagram->id, 'link' => '__ID__']) }}',
        },
    };
</script>
@endsection

@push('scripts')
@vite('resources/js/topology-editor.js')
@endpush
