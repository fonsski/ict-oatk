@extends('layouts.app')

@section('title', 'Детали заявки - ICT')

@section('content')
<div class="min-h-screen bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto">
        <!-- Back Link -->
        <div class="mb-8">
            <a href="{{ route('tickets.index') }}" class="inline-flex items-center text-sm font-medium text-gray-500 hover:text-gray-700">
                <svg class="w-5 h-5 mr-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m15 18-6-6 6-6"></path>
                </svg>
                Вернуться к заявкам
            </a>
        </div>

        <!-- Ticket Header -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
            <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between">
                <div class="flex-1 max-w-full">
                    <h1 class="text-2xl font-bold text-gray-900 mb-2 break-words overflow-hidden">#{{ $ticket->id }} {{ $ticket->title }}</h1>
                    <div class="flex flex-wrap gap-4 mb-4">
                        <!-- Status -->
                        @php
                            $statusColors = [
                                'open' => 'bg-blue-100 text-blue-800',
                                'in_progress' => 'bg-yellow-100 text-yellow-800',
                                'resolved' => 'bg-green-100 text-green-800',
                                'closed' => 'bg-gray-100 text-gray-800'
                            ];
                            $statusLabels = [
                                'open' => 'Открыта',
                                'in_progress' => 'В работе',
                                'resolved' => 'Решена',
                                'closed' => 'Закрыта'
                            ];
                            // Используем глобальные функции вместо объявления массивов здесь
                            $categoryLabels = [
                                'hardware' => 'Оборудование',
                                'software' => 'Программное обеспечение',
                                'network' => 'Сеть и интернет',
                                'account' => 'Учетная запись',
                                'other' => 'Другое'
                            ];
                        @endphp
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $statusColors[$ticket->status] }}">
                            @if($ticket->status === 'open')
                                <svg class="w-4 h-4 mr-1.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="10"></circle><path d="M12 6v6l4 2"></path>
                                </svg>
                            @elseif($ticket->status === 'in_progress')
                                <svg class="w-4 h-4 mr-1.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line>
                                </svg>
                            @elseif($ticket->status === 'resolved')
                                <svg class="w-4 h-4 mr-1.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><path d="M22 4 12 14.01l-3-3"></path>
                                </svg>
                            @else
                                <svg class="w-4 h-4 mr-1.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="10"></circle><path d="m15 9-6 6"></path><path d="m9 9 6 6"></path>
                                </svg>
                            @endif
                            {{ $statusLabels[$ticket->status] }}
                        </span>

                        <!-- Priority -->
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $ticket->priority == 'urgent' ? 'bg-red-200 text-red-900' : get_priority_badge_class($ticket->priority) }}">
                            {{ $ticket->priority == 'urgent' ? 'Срочный' : format_ticket_priority($ticket->priority) }}
                        </span>

                        <!-- Category -->
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-800">
                            <svg class="w-4 h-4 mr-1.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path><line x1="7" y1="7" x2="7.01" y2="7"></line>
                            </svg>
                            {{ isset($categoryLabels[$ticket->category]) ? $categoryLabels[$ticket->category] : 'Неизвестная категория' }}
                        </span>
                    </div>
                </div>

                <div class="mt-4 lg:mt-0 flex flex-wrap gap-2">
                    <!-- Action Buttons -->
                    @if(can_manage_ticket($ticket))
                        @if($ticket->status === 'open')
                            <form action="{{ route('tickets.start', $ticket) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                    Взять в работу
                                </button>
                            </form>
                        @endif

                        @if($ticket->status === 'in_progress')
                            <form action="{{ route('tickets.resolve', $ticket) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="whitespace-nowrap inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                    Отметить как решенную
                                </button>
                            </form>
                        @endif

                            @if($ticket->status === 'resolved')
                                <form action="{{ route('tickets.close', $ticket) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="whitespace-nowrap inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-gray-600 hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                                        Закрыть заявку
                                    </button>
                                </form>
                            @endif
                        </div>
                    @endif
            </div>
        </div>

        <!-- Ticket Details -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Description -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <h2 class="text-lg font-medium text-gray-900 mb-4">Описание проблемы</h2>
                    <div class="prose max-w-none ticket-description text-wrap text-block">
                        {{ $ticket->description }}
                    </div>
                </div>

                <!-- Comments -->
                @if($ticket->comments->count() > 0)
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <h2 class="text-lg font-medium text-gray-900 mb-4">Комментарии</h2>
                        <div class="space-y-6">
                            @foreach($ticket->comments as $comment)
                                @if($comment->is_system)
                                    <div class="flex space-x-4">
                                        <div class="flex-shrink-0">
                                            <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center">
                                                <svg class="w-6 h-6 text-blue-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"></path>
                                                </svg>
                                            </div>
                                        </div>
                                        <div class="flex-1">
                                            <div class="bg-blue-50 rounded-lg p-3">
                                                <div class="flex items-center justify-between">
                                                    <h3 class="text-sm font-medium text-blue-600">Система</h3>
                                                    <p class="text-sm text-gray-500">{{ $comment->created_at ? $comment->created_at->format('d.m.Y H:i') : '—' }}</p>
                                                </div>
                                                <div class="mt-1 text-sm text-gray-700 ticket-comment-content text-wrap text-block">
                                                    {{ $comment->content }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <div class="flex space-x-4">
                                        <div class="flex-shrink-0">
                                            <div class="w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center">
                                                <svg class="w-6 h-6 text-gray-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle>
                                                </svg>
                                            </div>
                                        </div>
                                        <div class="flex-1">
                                            <div class="flex items-center justify-between">
                                                <h3 class="text-sm font-medium text-gray-900">{{ $comment->user->name }}</h3>
                                                <p class="text-sm text-gray-500">{{ $comment->created_at ? $comment->created_at->format('d.m.Y H:i') : '—' }}</p>
                                            </div>
                                            <div class="mt-2 text-sm text-gray-700 ticket-comment-content text-wrap text-block">
                                                {{ $comment->content }}
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Add Comment -->
                @if($ticket->status !== 'closed')
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <h2 class="text-lg font-medium text-gray-900 mb-4">Добавить комментарий</h2>
                        <form action="{{ route('tickets.comment.store', $ticket) }}" method="POST" id="commentForm">
                            @csrf
                            <div>
                                <textarea name="content"
                                          id="commentContent"
                                          rows="4"
                                          required
                                          maxlength="1000"
                                          minlength="2"
                                          onkeyup="checkCommentLength(this)"
                                          class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('content') border-red-300 focus:ring-red-500 focus:border-red-500 @enderror"
                                          placeholder="Введите ваш комментарий..."></textarea>
                                <div class="flex justify-between mt-1">
                                    <div>
                                        @error('content')
                                            <p class="text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div id="charCounter" class="text-xs text-gray-500 font-medium">0/1000 символов</div>
                                </div>
                            </div>
                            <div class="mt-4">
                                <button type="submit"
                                        id="commentSubmitBtn"
                                        class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                    Отправить комментарий
                                </button>
                            </div>
                        </form>
                        <script>
                            // Функция для проверки длины комментария
                            function checkCommentLength(textarea) {
                                const charCounter = document.getElementById('charCounter');
                                const currentLength = textarea.value.length;
                                charCounter.textContent = currentLength + '/1000 символов';

                                // Меняем цвет счетчика, если приближаемся к лимиту
                                if (currentLength > 950 && currentLength < 1000) {
                                    charCounter.classList.remove('text-gray-500', 'text-red-500');
                                    charCounter.classList.add('text-orange-500');
                                } else if (currentLength >= 1000) {
                                    charCounter.classList.remove('text-gray-500', 'text-orange-500');
                                    charCounter.classList.add('text-red-500');

                                    // Обрезаем текст, если он превышает максимальную длину
                                    textarea.value = textarea.value.substring(0, 1000);
                                } else {
                                    charCounter.classList.remove('text-orange-500', 'text-red-500');
                                    charCounter.classList.add('text-gray-500');
                                }
                            }

                            document.addEventListener('DOMContentLoaded', function() {
                                const commentForm = document.getElementById('commentForm');
                                const commentBtn = document.getElementById('commentSubmitBtn');
                                const commentContent = document.getElementById('commentContent');
                                const charCounter = document.getElementById('charCounter');

                                // Инициализируем счетчик при загрузке страницы
                                if (commentContent && commentContent.value.length > 0) {
                                    checkCommentLength(commentContent);
                                }

                                // Счетчик символов
                                commentContent.addEventListener('input', function() {
                                    checkCommentLength(this);
                                });

                                commentForm.addEventListener('submit', function(e) {
                                    // Проверка длины комментария
                                    const commentLength = commentContent.value.trim().length;
                                    if (commentLength < 2) {
                                        e.preventDefault();
                                        alert('Комментарий должен содержать не менее 2 символов');
                                        commentContent.focus();
                                        return;
                                    }

                                    // Если кнопка уже отключена, прерываем отправку
                                    if (commentBtn.disabled) {
                                        e.preventDefault();
                                        return;
                                    }

                                    // Отключаем кнопку и меняем текст
                                    commentBtn.disabled = true;
                                    commentBtn.innerHTML = 'Отправка...';

                                    // Если сервер не ответил в течение 5 секунд, разблокируем кнопку
                                    setTimeout(function() {
                                        commentBtn.disabled = false;
                                        commentBtn.innerHTML = 'Отправить комментарий';
                                    }, 5000);
                                });
                            });
                        </script>
                    </div>
                @endif
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Ticket Info -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <h2 class="text-lg font-medium text-gray-900 mb-4">Информация о заявке</h2>
                    <dl class="space-y-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Автор</dt>
                            <dd class="mt-1 flex items-center">
                                <svg class="w-4 h-4 text-gray-400 mr-1.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle>
                                </svg>
                                <span class="text-sm text-gray-900">{{ $ticket->reporter_name }}</span>
                            </dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500">Телефон</dt>
                            <dd class="mt-1 flex items-center">
                                <svg class="w-4 h-4 text-gray-400 mr-1.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                                </svg>
                                <span class="text-sm text-gray-900 text-wrap">{{ !empty($ticket->reporter_phone) ? format_phone($ticket->reporter_phone) : 'Не указан' }}</span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Кабинет</dt>
                            <dd class="mt-1 flex items-center">
                                <svg class="w-4 h-4 text-gray-400 mr-1.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M3 21h18"></path>
                                    <path d="M5 21V7l8-4v18"></path>
                                    <path d="M19 21V11l-6-4"></path>
                                </svg>
                                @if($ticket->room)
                                <div>
                                    <div class="text-sm text-gray-900 text-wrap">{{ $ticket->room->number }} - {{ $ticket->room->name ?? $ticket->room->type_name }}</div>
                                    @if($ticket->room->building || $ticket->room->floor)
                                        <div class="text-xs text-gray-500 text-wrap">{{ $ticket->room->full_address }}</div>
                                    @endif
                                </div>
                                @else
                                <span class="text-sm text-gray-500">Не указан</span>
                                @endif
                            </dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500">Оборудование</dt>
                            <dd class="mt-1 flex items-center">
                                <svg class="w-4 h-4 text-gray-400 mr-1.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="2" y="6" width="20" height="12" rx="2"></rect>
                                    <path d="M6 12h12"></path>
                                </svg>
                                @if($ticket->equipment)
                                <a href="{{ route('equipment.show', $ticket->equipment) }}" class="text-sm text-blue-600 hover:text-blue-800 hover:underline text-wrap">
                                    {{ $ticket->equipment->name ?: 'Оборудование' }} ({{ $ticket->equipment->inventory_number }})
                                </a>
                                @else
                                <span class="text-sm text-gray-500">Не указано</span>
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Исполнитель</dt>
                            <dd class="mt-1 flex items-center">
                                <svg class="w-4 h-4 text-gray-400 mr-1.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="9" cy="7" r="4"></circle>
                                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                                </svg>
                                @if($ticket->assignedTo)
                                <span class="text-sm text-gray-900 text-wrap">{{ $ticket->assignedTo->name }}
                                    @if($ticket->assignedTo->role)
                                    <span class="text-xs text-gray-500 text-wrap">({{ $ticket->assignedTo->role->name }})</span>
                                    @endif
                                </span>
                                @else
                                <span class="text-sm text-gray-500 text-wrap">Не назначен</span>
                                @endif
                            </dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500">Локация</dt>
                            <dd class="mt-1 flex items-center">
                                <svg class="w-4 h-4 text-gray-400 mr-1.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                    <circle cx="12" cy="10" r="3"></circle>
                                </svg>
                                @if($ticket->location)
                                <span class="text-sm text-gray-900 text-wrap">{{ $ticket->location->name }}</span>
                                @else
                                <span class="text-sm text-gray-500">Не указана</span>
                                @endif
                            </dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500">Создано</dt>
                            <dd class="mt-1 flex items-center">
                                <svg class="w-4 h-4 text-gray-400 mr-1.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect width="18" height="18" x="3" y="4" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line>
                                </svg>
                                <span class="text-sm text-gray-900">{{ $ticket->created_at ? $ticket->created_at->format('d.m.Y H:i') : '—' }}</span>
                            </dd>
                        </div>

                        @if($ticket->updated_at->ne($ticket->created_at))
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Последнее обновление</dt>
                                <dd class="mt-1 flex items-center">
                                    <svg class="w-4 h-4 text-gray-400 mr-1.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                    </svg>
                                    <span class="text-sm text-gray-900">{{ $ticket->updated_at ? $ticket->updated_at->format('d.m.Y H:i') : '—' }}</span>
                                </dd>
                            </div>
                        @endif
                    </dl>
                </div>

                @if(Auth::check() && in_array(optional(Auth::user()->role)->slug, ['admin','master','technician']) && $ticket->status !== 'closed')
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <h3 class="text-md font-medium text-gray-900 mb-3">Назначить исполнителя</h3>
                        <form action="{{ route('tickets.assign', $ticket) }}" method="POST" id="assignForm">
                            @csrf
                            <div>
                                <label for="assigned_to_id" class="block text-sm font-medium text-gray-700">Исполнитель</label>
                                <select id="assigned_to_id" name="assigned_to_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="">Не назначено</option>
                                    @if(!empty($assignable))
                                        @foreach($assignable as $user)
                                            <option value="{{ $user->id }}" @if($ticket->assigned_to_id == $user->id) selected @endif>{{ $user->name }} @if($user->role) ({{ $user->role->name }}) @endif</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                            <div class="mt-4">
                                <button type="submit" id="assignButton" class="inline-flex items-center px-3 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">Назначить</button>
                            </div>
                        </form>

                        <script>
                            document.addEventListener('DOMContentLoaded', function() {
                                const assignForm = document.getElementById('assignForm');
                                const assignButton = document.getElementById('assignButton');
                                const assignSelect = document.getElementById('assigned_to_id');
                                const currentAssignee = '{{ $ticket->assigned_to_id }}';

                                // Отслеживаем отправку формы
                                assignForm.addEventListener('submit', function(e) {
                                    // Если не изменился исполнитель, не отправляем форму
                                    if (assignSelect.value === currentAssignee) {
                                        e.preventDefault();
                                        return;
                                    }

                                    // Предотвращаем многократное нажатие
                                    if (assignButton.disabled) {
                                        e.preventDefault();
                                        return;
                                    }

                                    // Отключаем кнопку и меняем текст
                                    assignButton.disabled = true;
                                    assignButton.innerHTML = 'Назначаем...';

                                    // Восстанавливаем кнопку через 3 секунды на случай ошибки
                                    setTimeout(function() {
                                        assignButton.disabled = false;
                                        assignButton.innerHTML = 'Назначить';
                                    }, 3000);
                                });
                            });
                        </script>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
