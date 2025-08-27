@extends('layouts.app')

@section('title', $room->name . ' - ICT')

@section('content')
<div class="max-w-7xl mx-auto px-6 py-8">
    <!-- Header -->
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between mb-8">
        <div class="mb-6 lg:mb-0">
            <div class="flex items-center space-x-4 mb-2">
                <h1 class="text-3xl font-bold text-slate-900">{{ $room->number }} - {{ $room->name }}</h1>
                {!! $room->status_badge !!}
                @if(!$room->is_active)
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                        Неактивен
                    </span>
                @endif
            </div>
            <p class="text-slate-600">{{ $room->full_address }}</p>
        </div>
        <div class="flex items-center space-x-4">
            <a href="{{ route('room.edit', $room) }}"
                class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 transition-colors duration-200">
                <svg class="w-4 h-4 mr-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                </svg>
                Редактировать
            </a>
            <a href="{{ route('room.index') }}"
                class="inline-flex items-center px-4 py-2 text-slate-600 hover:text-slate-900 transition-colors duration-200">
                <svg class="w-5 h-5 mr-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="19" y1="12" x2="5" y2="12"></line>
                    <polyline points="12 19 5 12 12 5"></polyline>
                </svg>
                Назад к списку
            </a>
        </div>
    </div>

    <!-- Success/Error Messages -->
    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6">
            <div class="flex items-center">
                <svg class="w-5 h-5 mr-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 12l2 2 4-4"></path>
                    <path d="M21 12c-1 0-2-1-2-2s1-2 2-2 2 1 2 2-1 2-2 2"></path>
                    <path d="M3 12c1 0 2-1 2-2s-1-2-2-2-2 1-2 2 1 2 2 2"></path>
                    <path d="M12 3c0 1-1 2-2 2s-2 1-2 2 1 2 2 2 2 1 2 2 1-2 2-2 2-1 2-2-1-2-2-2-2-1-2-2"></path>
                </svg>
                {{ session('success') }}
            </div>
        </div>
    @endif

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-blue-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                            <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
                        </svg>
                    </div>
                </div>
                <div class="ml-4">
                    <p class="text-2xl font-semibold text-slate-900">{{ $stats['total_equipment'] }}</p>
                    <p class="text-sm text-slate-600">Всего оборудования</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-green-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 12l2 2 4-4"></path>
                            <path d="M21 12c-1 0-2-1-2-2s1-2 2-2 2 1 2 2-1 2-2 2"></path>
                            <path d="M3 12c1 0 2-1 2-2s-1-2-2-2-2 1-2 2 1 2 2 2"></path>
                            <path d="M12 3c0 1-1 2-2 2s-2 1-2 2 1 2 2 2 2 1 2 2 1-2 2-2 2-1 2-2-1-2-2-2-2-1-2-2"></path>
                        </svg>
                    </div>
                </div>
                <div class="ml-4">
                    <p class="text-2xl font-semibold text-slate-900">{{ $stats['active_equipment'] }}</p>
                    <p class="text-sm text-slate-600">Рабочее оборудование</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-orange-100 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-orange-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                        </svg>
                    </div>
                </div>
                <div class="ml-4">
                    <p class="text-2xl font-semibold text-slate-900">{{ $stats['total_tickets'] }}</p>
                    <p class="text-sm text-slate-600">Всего заявок</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-purple-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <path d="M12 6v6l4 2"></path>
                        </svg>
                    </div>
                </div>
                <div class="ml-4">
                    <p class="text-2xl font-semibold text-slate-900">{{ $stats['recent_tickets'] }}</p>
                    <p class="text-sm text-slate-600">За последний месяц</p>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Main Information -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow-sm border border-slate-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200">
                    <h3 class="text-lg font-medium text-slate-900">Информация о кабинете</h3>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <dt class="text-sm font-medium text-slate-500">Номер кабинета</dt>
                            <dd class="mt-1 text-sm text-slate-900">{{ $room->number }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-slate-500">Название</dt>
                            <dd class="mt-1 text-sm text-slate-900">{{ $room->name }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-slate-500">Тип</dt>
                            <dd class="mt-1">{!! $room->type_badge !!}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-slate-500">Статус</dt>
                            <dd class="mt-1">{!! $room->status_badge !!}</dd>
                        </div>
                        @if($room->building)
                        <div>
                            <dt class="text-sm font-medium text-slate-500">Здание</dt>
                            <dd class="mt-1 text-sm text-slate-900">{{ $room->building }}</dd>
                        </div>
                        @endif
                        @if($room->floor)
                        <div>
                            <dt class="text-sm font-medium text-slate-500">Этаж</dt>
                            <dd class="mt-1 text-sm text-slate-900">{{ $room->floor }}</dd>
                        </div>
                        @endif
                        @if($room->capacity)
                        <div>
                            <dt class="text-sm font-medium text-slate-500">Вместимость</dt>
                            <dd class="mt-1 text-sm text-slate-900">{{ $room->capacity }} мест</dd>
                        </div>
                        @endif
                        <div>
                            <dt class="text-sm font-medium text-slate-500">Активность</dt>
                            <dd class="mt-1">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $room->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $room->is_active ? 'Активен' : 'Неактивен' }}
                                </span>
                            </dd>
                        </div>
                    </div>

                    @if($room->description)
                    <div class="mt-6">
                        <dt class="text-sm font-medium text-slate-500">Описание</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ $room->description }}</dd>
                    </div>
                    @endif

                    @if($room->notes)
                    <div class="mt-6">
                        <dt class="text-sm font-medium text-slate-500">Примечания</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ $room->notes }}</dd>
                    </div>
                    @endif

                    <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <dt class="text-sm font-medium text-slate-500">Создано</dt>
                            <dd class="mt-1 text-sm text-slate-900">{{ $room->created_at->format('d.m.Y H:i') }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-slate-500">Обновлено</dt>
                            <dd class="mt-1 text-sm text-slate-900">{{ $room->updated_at->format('d.m.Y H:i') }}</dd>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Equipment List -->
            @if($room->equipment->count() > 0)
            <div class="mt-8 bg-white rounded-lg shadow-sm border border-slate-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200">
                    <h3 class="text-lg font-medium text-slate-900">Оборудование в кабинете</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">
                                    Название
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">
                                    Тип
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">
                                    Статус
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">
                                    Последнее обслуживание
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-slate-200">
                            @foreach($room->equipment as $equipment)
                                <tr class="hover:bg-slate-50">
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-medium text-slate-900">{{ $equipment->name }}</div>
                                        @if($equipment->model)
                                            <div class="text-sm text-slate-500">{{ $equipment->model }}</div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">
                                        {{ $equipment->type ?? '—' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                            {{ $equipment->status->slug === 'working' ? 'bg-green-100 text-green-800' :
                                               ($equipment->status->slug === 'maintenance' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                                            {{ $equipment->status->name }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">
                                        {{ $equipment->last_maintenance_at ? $equipment->last_maintenance_at->format('d.m.Y') : 'Не проводилось' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div>
            <!-- Contact Information -->
            @if($room->responsible_person || $room->phone || $room->responsibleUser)
            <div class="bg-white rounded-lg shadow-sm border border-slate-200 overflow-hidden mb-6">
                <div class="px-6 py-4 border-b border-slate-200">
                    <h3 class="text-lg font-medium text-slate-900">Контактная информация</h3>
                </div>
                <div class="p-6">
                    @if($room->responsible_person && !$room->responsibleUser)
                    <div class="mb-4">
                        <dt class="text-sm font-medium text-slate-500">Ответственный</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ $room->responsible_person }}</dd>
                    </div>
                    @endif

                    <div class="mb-4">
                        <dt class="text-sm font-medium text-slate-500">Ответственный пользователь</dt>
                        <dd class="mt-1 text-sm text-slate-900">
                            @if($room->responsibleUser)
                                <div class="mb-3 text-slate-700">
                                    Текущий ответственный: <strong>{{ $room->responsibleUser->name }}</strong>
                                    @if($room->responsibleUser->phone)
                                        <br>Телефон: {{ $room->responsibleUser->phone }}
                                    @endif
                                </div>
                            @endif
                            <form action="{{ route('room.update', $room) }}" method="POST">
                                @csrf
                                @method('PUT')
                                <div class="flex flex-col sm:flex-row items-start gap-2">
                                    <div class="w-full sm:w-auto">
                                        <input type="text"
                                            id="user_search"
                                            placeholder="Поиск пользователя..."
                                            class="w-full mb-2 px-3 py-1.5 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                        <select id="responsible_user_id" name="responsible_user_id"
                                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                            <option value="">Не назначен</option>
                                            @foreach($users as $user)
                                                <option value="{{ $user->id }}" data-name="{{ strtolower($user->name) }}" {{ $room->responsible_user_id == $user->id ? 'selected' : '' }}>
                                                    {{ $user->name }} {{ $user->phone ? "({$user->phone})" : "" }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <button type="submit"
                                        class="w-full sm:w-auto inline-flex items-center justify-center px-3 py-1.5 bg-blue-600 text-white text-xs font-medium rounded-md hover:bg-blue-700 transition-colors duration-200">
                                        Сохранить
                                    </button>
                                </div>
                            </form>
                        </dd>
                    </div>
                    @if($room->phone)
                    <div>
                        <dt class="text-sm font-medium text-slate-500">Телефон</dt>
                        <dd class="mt-1 text-sm text-slate-900">
                            <a href="tel:{{ $room->phone }}" class="text-blue-600 hover:text-blue-800">{{ $room->phone }}</a>
                        </dd>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            <!-- Quick Actions -->
            <div class="bg-white rounded-lg shadow-sm border border-slate-200 overflow-hidden mb-6">
                <div class="px-6 py-4 border-b border-slate-200">
                    <h3 class="text-lg font-medium text-slate-900">Быстрые действия</h3>
                </div>
                <div class="p-6">
                    <div class="space-y-3">
                        <!-- Change Status -->
                        <div class="relative">
                            <button type="button" id="status-menu-button"
                                    class="w-full flex items-center justify-between px-3 py-2 bg-slate-50 text-slate-700 rounded-lg hover:bg-slate-100 transition-colors duration-200">
                                <span class="flex items-center">
                                    <svg class="w-4 h-4 mr-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="12" cy="12" r="3"></circle>
                                        <path d="M12 1v6m0 6v6m11-7h-6m-6 0H1"></path>
                                    </svg>
                                    Изменить статус
                                </span>
                                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="6,9 12,15 18,9"></polyline>
                                </svg>
                            </button>

                            <div id="status-dropdown" class="hidden absolute left-0 mt-2 w-full bg-white rounded-md shadow-lg border border-gray-200 z-10">
                                <div class="py-1">
                                    @foreach(\App\Models\Room::STATUSES as $status => $statusName)
                                        @if($status !== $room->status)
                                            <form action="{{ route('room.change-status', $room) }}" method="POST" class="inline w-full">
                                                @csrf
                                                <input type="hidden" name="status" value="{{ $status }}">
                                                <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                    {{ $statusName }}
                                                </button>
                                            </form>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <!-- Toggle Active -->
                        <form action="{{ route('room.toggle-active', $room) }}" method="POST">
                            @csrf
                            <button type="submit"
                                    class="w-full flex items-center px-3 py-2 {{ $room->is_active ? 'bg-orange-50 text-orange-700 hover:bg-orange-100' : 'bg-green-50 text-green-700 hover:bg-green-100' }} rounded-lg transition-colors duration-200">
                                @if($room->is_active)
                                    <svg class="w-4 h-4 mr-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="12" cy="12" r="3"></circle>
                                        <path d="M12 1v6m0 6v6m11-7h-6m-6 0H1"></path>
                                    </svg>
                                    Деактивировать кабинет
                                @else
                                    <svg class="w-4 h-4 mr-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M9 12l2 2 4-4"></path>
                                        <path d="M21 12c-1 0-2-1-2-2s1-2 2-2 2 1 2 2-1 2-2 2"></path>
                                        <path d="M3 12c1 0 2-1 2-2s-1-2-2-2-2 1-2 2 1 2 2 2"></path>
                                        <path d="M12 3c0 1-1 2-2 2s-2 1-2 2 1 2 2 2 2 1 2 2 1-2 2-2 2-1 2-2-1-2-2-2-2-1-2-2"></path>
                                    </svg>
                                    Активировать кабинет
                                @endif
                            </button>
                        </form>

                        <!-- Create Ticket -->
                        <a href="{{ route('tickets.create', ['room_id' => $room->id]) }}"
                           class="w-full flex items-center px-3 py-2 bg-blue-50 text-blue-700 rounded-lg hover:bg-blue-100 transition-colors duration-200">
                            <svg class="w-4 h-4 mr-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                            </svg>
                            Создать заявку
                        </a>
                    </div>
                </div>
            </div>

            <!-- Recent Tickets -->
            @if($room->tickets->take(5)->count() > 0)
            <div class="bg-white rounded-lg shadow-sm border border-slate-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200">
                    <h3 class="text-lg font-medium text-slate-900">Последние заявки</h3>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        @foreach($room->tickets->take(5) as $ticket)
                            <div class="flex items-start space-x-3 hover:bg-slate-50 p-2 rounded-md transition-colors duration-200">
                                <div class="flex-shrink-0">
                                    @php
                                        $statusColors = [
                                            'open' => 'bg-blue-400',
                                            'in_progress' => 'bg-yellow-400',
                                            'resolved' => 'bg-green-400',
                                            'closed' => 'bg-slate-400'
                                        ];
                                        $statusColor = $statusColors[$ticket->status] ?? 'bg-blue-400';
                                    @endphp
                                    <div class="w-2 h-2 {{ $statusColor }} rounded-full mt-2"></div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <a href="{{ route('tickets.show', $ticket) }}" class="hover:text-blue-600">
                                        <p class="text-sm font-medium text-slate-900 truncate">
                                            #{{ $ticket->id }} - {{ $ticket->title }}
                                        </p>
                                        <div class="flex items-center mt-1">
                                            <span class="text-xs px-1.5 py-0.5 rounded-full mr-2
                                                @if($ticket->status === 'open') bg-blue-100 text-blue-800
                                                @elseif($ticket->status === 'in_progress') bg-yellow-100 text-yellow-800
                                                @elseif($ticket->status === 'resolved') bg-green-100 text-green-800
                                                @else bg-slate-100 text-slate-800 @endif">
                                                {{ $ticket->status === 'open' ? 'Открыта' :
                                                   ($ticket->status === 'in_progress' ? 'В работе' :
                                                   ($ticket->status === 'resolved' ? 'Решена' : 'Закрыта')) }}
                                            </span>
                                            <p class="text-xs text-slate-500">
                                                {{ $ticket->created_at->format('d.m.Y H:i') }}
                                            </p>
                                        </div>
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    @if($room->tickets->count() > 5)
                        <div class="mt-4 pt-4 border-t border-slate-200">
                            <a href="{{ route('tickets.index', ['room_id' => $room->id]) }}" class="text-sm text-blue-600 hover:text-blue-800">
                                Показать все заявки ({{ $room->tickets->count() }})
                            </a>
                        </div>
                    @endif
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

<script>
// Status dropdown functionality
document.addEventListener('DOMContentLoaded', function() {
    const statusButton = document.getElementById('status-menu-button');
    const statusDropdown = document.getElementById('status-dropdown');

    if (statusButton && statusDropdown) {
        statusButton.addEventListener('click', function() {
            statusDropdown.classList.toggle('hidden');
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            if (!statusButton.contains(event.target) && !statusDropdown.contains(event.target)) {
                statusDropdown.classList.add('hidden');
            }
        });
    }
});
</script>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const userSearch = document.getElementById('user_search');
        const userSelect = document.getElementById('responsible_user_id');
        const userOptions = userSelect.querySelectorAll('option');

        userSearch.addEventListener('input', function() {
            const searchTerm = userSearch.value.toLowerCase().trim();

            userOptions.forEach(option => {
                if (option.value === '') return; // Пропускаем опцию "Не выбран"

                const userName = option.getAttribute('data-name');
                const shouldShow = userName.includes(searchTerm);

                option.style.display = shouldShow ? '' : 'none';
            });
        });
    });
</script>
@endpush
