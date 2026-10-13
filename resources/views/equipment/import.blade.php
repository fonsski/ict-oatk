@extends('layouts.app')

@section('title', 'Импорт оборудования - ICT Help')

@php
    $descriptions = [
        'inventory_number' => ['Да', 'Инвентарный номер, только цифры (до 20). По нему система находит оборудование.'],
        'accounting_number' => ['Нет', 'Учётный номер вида А1-сотрудник-001.'],
        'name' => ['Нет', 'Название, от 2 до 255 символов.'],
        'model' => ['Нет', 'Модель.'],
        'serial_number' => ['Нет', 'Серийный номер.'],
        'category' => ['Нет', 'Название категории, как в системе.'],
        'status' => ['Нет', 'working, in_service, broken, decommissioned или русское название. У нового пусто = working.'],
        'room_number' => ['Нет', 'Номер кабинета, как в системе.'],
        'room_name' => ['Нет', 'Название кабинета — нужно, только если кабинета ещё нет и включено создание.'],
        'operating_system' => ['Нет', 'Название ОС. Сохраняется для категорий, где ОС предусмотрена.'],
        'has_warranty' => ['Нет', 'true/false или да/нет.'],
        'warranty_end_date' => ['Нет', 'Дата вида 2026-12-31 или 31.12.2026.'],
        'last_service_date' => ['Нет', 'Дата последнего обслуживания, не позже сегодняшней.'],
        'service_comment' => ['Нет', 'Комментарий об обслуживании, 5–500 символов.'],
        'known_issues' => ['Нет', 'Известные проблемы, 5–500 символов.'],
    ];
@endphp

@section('content')
<div class="max-w-7xl mx-auto px-6 py-8">
    <div class="max-w-3xl mx-auto">
        <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6 mb-6">
            <div class="flex justify-between items-start mb-6">
                <h1 class="text-2xl font-bold text-slate-900">Импорт оборудования</h1>
                <a href="{{ route('equipment.index') }}" class="text-sm text-slate-500 underline">К списку</a>
            </div>

            @if ($errors->any())
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-4">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('equipment.import.preview') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="mb-4">
                    <label for="file" class="block text-slate-700 text-sm font-bold mb-2">Файл CSV или JSON *</label>
                    <input type="file" name="file" id="file" accept=".csv,.json,.txt" required
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-slate-700">
                    <p class="text-xs text-slate-500 mt-1">До 5 МБ и до 2000 записей. Файл сначала только проверяется, в систему ничего не попадёт без вашего подтверждения.</p>
                </div>

                <div class="mb-4">
                    <label for="existing" class="block text-slate-700 text-sm font-bold mb-2">Если оборудование с таким инвентарным номером уже есть</label>
                    <select name="existing" id="existing" class="w-full rounded-lg border-slate-300 px-3 py-2 text-slate-700">
                        <option value="skip" @selected(old('existing', 'skip') === 'skip')>Пропустить — ничего не менять</option>
                        <option value="update" @selected(old('existing') === 'update')>Обновить данные из файла (пустые ячейки не затирают)</option>
                    </select>
                </div>

                <div class="mb-6">
                    <label class="flex items-center">
                        <input type="hidden" name="create_missing" value="0">
                        <input type="checkbox" name="create_missing" value="1" @checked(old('create_missing'))
                               class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                        <span class="ml-2 text-sm text-slate-700">Создавать недостающие категории и кабинеты</span>
                    </label>
                    <p class="text-xs text-slate-500 mt-1">Если выключено, незнакомая категория или кабинет считаются ошибкой.</p>
                </div>

                <div class="flex items-center justify-between">
                    <a href="{{ route('equipment.import.template') }}" class="text-sm text-blue-600 underline">Скачать шаблон CSV</a>
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        Проверить файл
                    </button>
                </div>
            </form>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
            <h2 class="text-lg font-semibold text-slate-900 mb-3">Формат файла</h2>
            <p class="text-sm text-slate-600 mb-3">
                CSV: первая строка — названия колонок, разделитель «;» или «,», кодировка UTF-8 или Windows-1251.
                JSON: массив объектов, ключи — те же названия. Незнакомые колонки игнорируются.
                Пустая ячейка значит «не менять», очистить поле через импорт нельзя.
            </p>
            <p class="text-sm text-slate-600 mb-4">
                В Excel сделайте колонку <code>inventory_number</code> текстовой, иначе Excel обрежет ведущие нули
                и испортит длинные числа.
            </p>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left text-slate-500 border-b border-slate-200">
                            <th class="py-2 pr-4">Колонка</th>
                            <th class="py-2 pr-4">Обязательна</th>
                            <th class="py-2">Что записать</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($fields as $field)
                            <tr class="border-b border-slate-100">
                                <td class="py-2 pr-4 font-mono text-slate-800">{{ $field }}</td>
                                <td class="py-2 pr-4 text-slate-600">{{ $descriptions[$field][0] }}</td>
                                <td class="py-2 text-slate-600">{{ $descriptions[$field][1] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <h3 class="text-sm font-semibold text-slate-900 mt-5 mb-2">Пример JSON</h3>
            <pre class="bg-slate-50 border border-slate-200 rounded p-3 text-xs overflow-x-auto">[
  {
    "inventory_number": "2101344347",
    "name": "Принтер Canon",
    "category": "Принтер",
    "status": "working",
    "room_number": "4-ка"
  }
]</pre>
        </div>
    </div>
</div>
@endsection
