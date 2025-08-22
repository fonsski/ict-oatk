@extends('layouts.app')

@section('title', 'Мои заявки - ICT')

@section('content')
<div class="container-width section-padding">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-8">
            <div>
            <h1 class="text-3xl font-bold text-slate-900 mb-2">Мои заявки</h1>
            <p class="text-slate-600">Управляйте вашими обращениями в службу поддержки</p>
            </div>
        <a href="{{ route('tickets.create') }}" class="btn-primary mt-4 sm:mt-0">
                    Новая заявка
                </a>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="card p-6 text-center">
            <div class="text-2xl font-bold text-slate-900 mb-1">{{ $tickets->where('status', 'open')->count() }}</div>
            <div class="text-sm text-slate-600">Открытые</div>
        </div>
        <div class="card p-6 text-center">
            <div class="text-2xl font-bold text-slate-900 mb-1">{{ $tickets->where('status', 'in_progress')->count() }}</div>
            <div class="text-sm text-slate-600">В работе</div>
        </div>
        <div class="card p-6 text-center">
            <div class="text-2xl font-bold text-slate-900 mb-1">{{ $tickets->where('status', 'resolved')->count() }}</div>
            <div class="text-sm text-slate-600">Решённые</div>
        </div>
        <div class="card p-6 text-center">
            <div class="text-2xl font-bold text-slate-900 mb-1">{{ $tickets->where('status', 'closed')->count() }}</div>
            <div class="text-sm text-slate-600">Закрытые</div>
            </div>
        </div>

        <!-- Filters -->
    <div class="card p-6 mb-8">
        <h3 class="text-lg font-semibold text-slate-900 mb-4">Фильтры</h3>
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                <label for="status" class="form-label">Статус</label>
                <select id="status" name="status" class="form-input">
                        <option value="">Все статусы</option>
                        <option value="open" {{ request('status') == 'open' ? 'selected' : '' }}>Открытые</option>
                        <option value="in_progress" {{ request('status') == 'in_progress' ? 'selected' : '' }}>В работе</option>
                    <option value="resolved" {{ request('status') == 'resolved' ? 'selected' : '' }}>Решённые</option>
                        <option value="closed" {{ request('status') == 'closed' ? 'selected' : '' }}>Закрытые</option>
                    </select>
                </div>
                <div>
                <label for="priority" class="form-label">Приоритет</label>
                <select id="priority" name="priority" class="form-input">
                        <option value="">Все приоритеты</option>
                        <option value="low" {{ request('priority') == 'low' ? 'selected' : '' }}>Низкий</option>
                        <option value="medium" {{ request('priority') == 'medium' ? 'selected' : '' }}>Средний</option>
                        <option value="high" {{ request('priority') == 'high' ? 'selected' : '' }}>Высокий</option>
                    </select>
                </div>
                <div>
                <label for="category" class="form-label">Категория</label>
                <select id="category" name="category" class="form-input">
                    <option value="">Все категории</option>
                    @foreach($categories ?? [] as $category)
                        <option value="{{ $category->id }}" {{ request('category') == $category->id ? 'selected' : '' }}>
                            {{ $category->name }}
                        </option>
                        @endforeach
                    </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="btn-primary btn-full">
                    Применить
                </button>
            </div>
            </form>
        </div>

    <!-- Tickets Table -->
    <div class="card overflow-hidden">
        @if($tickets->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th class="px-6 py-4 text-left text-sm font-semibold text-slate-900">Заявка</th>
                            <th class="px-6 py-4 text-left text-sm font-semibold text-slate-900">Статус</th>
                            <th class="px-6 py-4 text-left text-sm font-semibold text-slate-900">Приоритет</th>
                            <th class="px-6 py-4 text-left text-sm font-semibold text-slate-900">Категория</th>
                            <th class="px-6 py-4 text-left text-sm font-semibold text-slate-900">Дата</th>
                            <th class="px-6 py-4 text-left text-sm font-semibold text-slate-900">Действия</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @foreach($tickets as $ticket)
                            <tr class="hover:bg-slate-50 transition-colors duration-200">
                                <td class="px-6 py-4">
                                    <div>
                                        <a href="{{ route('tickets.show', $ticket) }}" 
                                           class="text-slate-900 font-medium hover:text-blue-600 transition-colors duration-200">
                                            {{ $ticket->title }}
                                        </a>
                                        <p class="text-sm text-slate-600 mt-1 line-clamp-2">
                                            {{ Str::limit($ticket->description, 80) }}
                                        </p>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    @php
                                        $statusColors = [
                                            'open' => 'bg-blue-100 text-blue-800',
                                            'in_progress' => 'bg-yellow-100 text-yellow-800',
                                            'resolved' => 'bg-green-100 text-green-800',
                                            'closed' => 'bg-slate-100 text-slate-800'
                                        ];
                                        $statusLabels = [
                                            'open' => 'Открыта',
                                            'in_progress' => 'В работе',
                                            'resolved' => 'Решена',
                                            'closed' => 'Закрыта'
                                        ];
                                    @endphp
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$ticket->status] ?? 'bg-slate-100 text-slate-800' }}">
                                        {{ $statusLabels[$ticket->status] ?? $ticket->status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    @php
                                        $priorityColors = [
                                            'low' => 'bg-green-100 text-green-800',
                                            'medium' => 'bg-yellow-100 text-yellow-800',
                                            'high' => 'bg-red-100 text-red-800'
                                        ];
                                        $priorityLabels = [
                                            'low' => 'Низкий',
                                            'medium' => 'Средний',
                                            'high' => 'Высокий'
                                        ];
                                    @endphp
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $priorityColors[$ticket->priority] ?? 'bg-slate-100 text-slate-800' }}">
                                        {{ $priorityLabels[$ticket->priority] ?? $ticket->priority }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-sm text-slate-600">
                                        {{ $ticket->category->name ?? '—' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-slate-600">
                                        <div>{{ $ticket->created_at->format('d.m.Y') }}</div>
                                        <div class="text-xs text-slate-500">{{ $ticket->created_at->format('H:i') }}</div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <a href="{{ route('tickets.show', $ticket) }}" 
                                       class="text-blue-600 hover:text-blue-700 font-medium text-sm">
                                        Просмотр
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-16">
                <div class="mx-auto w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-slate-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-medium text-slate-900 mb-2">Заявок пока нет</h3>
                <p class="text-slate-600 mb-6">Создайте первую заявку, и мы поможем решить вашу проблему</p>
                <a href="{{ route('tickets.create') }}" class="btn-primary btn-lg">
                    Создать заявку
                </a>
                </div>
            @endif
    </div>

    <!-- Pagination -->
    @if($tickets->hasPages())
        <div class="mt-8">
            {{ $tickets->links() }}
        </div>
    @endif
</div>
@endsection
