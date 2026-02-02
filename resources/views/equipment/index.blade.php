@extends('layouts.app')

@section('title', 'Оборудование')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Оборудование</h1>

        <a href="{{ route('equipment.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
            Добавить оборудование
        </a>
    </div>

    @if(session('success'))
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">
        {{ session('success') }}
    </div>
    @endif

    <div class="bg-white shadow-md rounded-lg overflow-hidden">
        <div class="p-4 border-b">
            <form method="GET" id="filters-form" class="flex flex-wrap gap-3 items-center">
                <div>
                    <select name="search_by" class="rounded border-gray-300 px-3 py-2">
                        <option value="inventory_number" {{ request('search_by') == 'inventory_number' ? 'selected' : '' }}>Инв. номер</option>
                        <option value="name" {{ request('search_by') == 'name' ? 'selected' : '' }}>Название</option>
                        <!-- Опция поиска по локации удалена -->
                        <option value="status" {{ request('search_by') == 'status' ? 'selected' : '' }}>Статус</option>
                    </select>
                </div>
                <div>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Поиск" class="rounded border-gray-300 px-3 py-2" />
                </div>
                <div>
                    <select name="status_id" class="rounded border-gray-300 px-3 py-2">
                        <option value="">Все статусы</option>
                        @foreach($statuses as $s)
                        <option value="{{ $s->id }}" {{ request('status_id') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <select name="category_id" class="rounded border-gray-300 px-3 py-2">
                        <option value="">Все категории</option>
                        @foreach($categories as $c)
                        <option value="{{ $c->id }}" {{ request('category_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <!-- Фильтр по локации удален -->
                <div>
                    <select name="warranty" class="rounded border-gray-300 px-3 py-2">
                        <option value="">Все гарантии</option>
                        <option value="active" {{ request('warranty') == 'active' ? 'selected' : '' }}>Действующие</option>
                        <option value="expired" {{ request('warranty') == 'expired' ? 'selected' : '' }}>Истекшие</option>
                        <option value="none" {{ request('warranty') == 'none' ? 'selected' : '' }}>Без гарантии</option>
                    </select>
                </div>
                <!-- Фильтр по локации удален -->
                <div>
                    <button type="submit" class="bg-gray-800 text-white px-3 py-2 rounded">Применить</button>
                </div>
                <div>
                    <a href="{{ route('equipment.index') }}" class="text-sm text-gray-500 underline">Сбросить</a>
                </div>
            </form>
        </div>

        <!-- Loading indicator -->
        <div id="loading-indicator" class="hidden bg-blue-50 border-b border-blue-200 px-4 py-3">
            <div class="flex items-center">
                <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-600 mr-3"></div>
                <span class="text-blue-700 text-sm">Поиск...</span>
            </div>
        </div>

        <!-- Equipment table container -->
        <div id="equipment-table">
            @include('equipment.partials.table', ['items' => $equipment])
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.querySelector('input[name="search"]');
    const searchBySelect = document.querySelector('select[name="search_by"]');
    const statusSelect = document.querySelector('select[name="status_id"]');
    const categorySelect = document.querySelector('select[name="category_id"]');
    const warrantySelect = document.querySelector('select[name="warranty"]');
    // Селектор локации удален
    const form = document.getElementById('filters-form');

    let searchTimeout;

    // Функция для выполнения поиска
    function performSearch() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            // Показать индикатор загрузки
            showLoadingIndicator();

            // Отправить форму
            const formData = new FormData(form);
            const params = new URLSearchParams(formData);

            fetch('{{ route("equipment.search") }}?' + params.toString())
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Обновить таблицу с HTML из partial
                        const equipmentTable = document.getElementById('equipment-table');
                        if (equipmentTable) {
                            equipmentTable.innerHTML = data.html;
                        }
                    } else {
                        console.error('API вернул ошибку:', data.message);
                    }
                    hideLoadingIndicator();
                })
                .catch(error => {
                    console.error('Ошибка поиска:', error);
                    hideLoadingIndicator();
                });
        }, 300); // Задержка 300ms для избежания избыточных запросов
    }

    function showLoadingIndicator() {
        const indicator = document.getElementById('loading-indicator');
        const table = document.getElementById('equipment-table');
        if (indicator) indicator.classList.remove('hidden');
        if (table) table.style.opacity = '0.5';
    }

    function hideLoadingIndicator() {
        const indicator = document.getElementById('loading-indicator');
        const table = document.getElementById('equipment-table');
        if (indicator) indicator.classList.add('hidden');
        if (table) table.style.opacity = '1';
    }

    // Добавить обработчики событий
    if (searchInput) {
        searchInput.addEventListener('input', performSearch);
    }

    if (searchBySelect) {
        searchBySelect.addEventListener('change', performSearch);
    }

    if (statusSelect) {
        statusSelect.addEventListener('change', performSearch);
    }

    if (warrantySelect) {
        warrantySelect.addEventListener('change', performSearch);
    }

    if (categorySelect) {
        categorySelect.addEventListener('change', performSearch);
    }

    // Обработчик события для локации удален

    // Обработчик для кнопки "Применить" (оставляем для совместимости)
    const submitBtn = document.querySelector('button[type="submit"]');
    if (submitBtn) {
        submitBtn.addEventListener('click', function(e) {
            e.preventDefault();
            performSearch();
        });
    }
});
</script>
@endpush

@endsection
