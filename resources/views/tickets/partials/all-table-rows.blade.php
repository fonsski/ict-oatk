@foreach($tickets as $ticket)
    <tr class="hover:bg-slate-50 transition-all duration-300 animate-fade-in" data-ticket-id="{{ $ticket->id }}">
        <td class="px-6 py-4">
            <div>
                <a href="{{ route('tickets.show', $ticket) }}"
                   class="text-slate-900 font-medium hover:text-blue-600 transition-all duration-300 break-words inline-block">
                    <span class="line-clamp-2">{{ $ticket->title }}</span>
                </a>
                <p class="text-sm text-slate-600 mt-1 break-words whitespace-pre-line line-clamp-2">
                    {{ Str::limit($ticket->description, 120) }}
                </p>
                @if($ticket->room)
                    <div class="text-xs text-slate-500 mt-1">
                        🏢 {{ $ticket->room->number }} - {{ $ticket->room->name ?? $ticket->room->type_name }}
                    </div>
                @elseif($ticket->location)
                    <div class="text-xs text-slate-500 mt-1">
                        📍 {{ $ticket->location->name }}
                    </div>
                @endif
            </div>
        </td>
        <td class="px-6 py-4">
            <div class="text-sm">
                <div class="font-medium text-slate-900">{{ $ticket->reporter_name ?: '—' }}</div>
                <div class="text-slate-600">{{ !empty($ticket->reporter_phone) ? format_phone($ticket->reporter_phone) : '—' }}</div>
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
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$ticket->status] ?? 'bg-slate-100 text-slate-800' }} transition-all duration-300" style="white-space: nowrap; text-align: center;">
                {{ $statusLabels[$ticket->status] ?? $ticket->status }}
            </span>
        </td>
        <td class="px-6 py-4">
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $ticket->priority == 'urgent' ? 'bg-red-200 text-red-900' : get_priority_badge_class($ticket->priority) }}" style="white-space: nowrap; text-align: center;">
                {{ $ticket->priority == 'urgent' ? 'Срочный' : format_ticket_priority($ticket->priority) }}
            </span>
        </td>
        <td class="px-6 py-4">
            @if($ticket->assignedTo)
                <div class="flex items-center">
                    <span class="text-sm text-slate-900">{{ $ticket->assignedTo->name }}</span>
                </div>
            @else
                <span class="text-sm text-slate-500 italic">Не назначено</span>
            @endif
        </td>
        <td class="px-6 py-4">
            <div class="text-sm text-slate-600">
                <div>{{ $ticket->created_at->format('d.m.Y') }}</div>
                <div class="text-xs text-slate-500">{{ $ticket->created_at->format('H:i') }}</div>
                @if($ticket->updated_at != $ticket->created_at)
                    <div class="text-xs text-slate-400">обн. {{ $ticket->updated_at->format('d.m H:i') }}</div>
                @endif
            </div>
        </td>
        <td class="px-6 py-4">
            <div class="flex items-center gap-2">
                <a href="{{ route('tickets.show', $ticket) }}"
                   class="text-blue-600 hover:text-blue-700 font-medium text-sm transition-all duration-300 hover:underline whitespace-nowrap">
                    Просмотр
                </a>
                @if(user_can_manage_tickets())
                    <div class="relative z-50" data-dropdown>
                            <button type="button" class="text-slate-500 hover:text-slate-700 p-2 transition-all duration-300 rounded-full hover:bg-slate-100" data-dropdown-toggle>
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z"/>
                                </svg>
                            </button>
                        <div class="absolute right-0 mt-2 w-56 bg-white rounded-md shadow-xl border border-slate-200 z-50 hidden animate-fade-in" data-dropdown-menu style="min-width: 12rem; max-width: 16rem;">
                            <div class="py-1">
                                @if($ticket->status !== 'in_progress' && $ticket->status !== 'closed')
                                    <form action="{{ route('tickets.start', $ticket) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="block w-full text-left px-4 py-3 text-sm text-slate-700 hover:bg-slate-100 transition">
                                            Взять в работу
                                        </button>
                                    </form>
                                @endif
                                @if($ticket->status === 'in_progress')
                                    <form action="{{ route('tickets.resolve', $ticket) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="block w-full text-left px-4 py-3 text-sm text-slate-700 hover:bg-slate-100 transition">
                                            Отметить решённой
                                        </button>
                                    </form>
                                @endif
                                @if($ticket->status === 'resolved')
                                    <form action="{{ route('tickets.close', $ticket) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="block w-full text-left px-4 py-3 text-sm text-slate-700 hover:bg-slate-100 transition">
                                            Закрыть заявку
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </td>
    </tr>
@endforeach

<script>
// Инициализация меню действий для каждой строки таблицы
function initTableDropdowns() {
    // Обработка выпадающих меню в таблице
    document.querySelectorAll('[data-dropdown]').forEach(function(dropdown) {
        const toggle = dropdown.querySelector('[data-dropdown-toggle]');
        const menu = dropdown.querySelector('[data-dropdown-menu]');

        if (toggle && menu) {
            toggle.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();

                console.log('Toggle clicked', toggle);
                console.log('Menu', menu);

                // Закрыть все другие меню
                document.querySelectorAll('[data-dropdown-menu]').forEach(function(otherMenu) {
                    if (otherMenu !== menu) {
                        otherMenu.classList.add('hidden');
                    }
                });

                document.querySelectorAll('[data-dropdown-toggle]').forEach(function(otherToggle) {
                    if (otherToggle !== toggle) {
                        otherToggle.classList.remove('bg-slate-100');
                    }
                });

                document.querySelectorAll('[data-dropdown-toggle]').forEach(function(otherToggle) {
                    if (otherToggle !== toggle) {
                        otherToggle.classList.remove('bg-slate-100');
                    }
                });

                // Переключить текущее меню
                menu.classList.toggle('hidden');
                toggle.classList.toggle('bg-slate-100');

                // Корректное позиционирование меню
                const rect = toggle.getBoundingClientRect();
                const rightSpace = window.innerWidth - rect.right;

                // Сбрасываем предыдущие стили
                menu.style.left = '';
                menu.style.right = '';
                menu.style.top = '';
                menu.style.position = 'absolute';
                menu.style.zIndex = '100';
                menu.style.boxShadow = '0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05)';
                menu.style.maxHeight = '80vh';
                menu.style.overflowY = 'auto';

                if (rightSpace < 200) {
                    // Недостаточно места справа, располагаем слева
                    menu.style.left = 'auto';
                    menu.style.right = '0';
                } else {
                    // Достаточно места справа
                    menu.style.left = '0';
                    menu.style.right = 'auto';
                }

                // Устанавливаем позицию по вертикали
                menu.style.top = 'calc(100% + 0.5rem)';

                // Убеждаемся, что меню видно
                // Максимальная высота и прокрутка для больших меню
                menu.style.maxHeight = '80vh';
                menu.style.overflowY = 'auto';
            });
        }
    });
}

// Инициализация при загрузке страницы
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(initTableDropdowns, 100); // Небольшая задержка для уверенности, что DOM полностью загружен

    // Закрытие меню при клике вне его
    document.addEventListener('click', function(e) {
        if (!e.target.closest('[data-dropdown]')) {
            document.querySelectorAll('[data-dropdown-menu]').forEach(function(menu) {
                menu.classList.add('hidden');
            });
            document.querySelectorAll('[data-dropdown-toggle]').forEach(function(toggle) {
                toggle.classList.remove('bg-slate-100');
            });
        }
    });

    // Добавление визуального эффекта при клике на кнопки
    document.querySelectorAll('[data-dropdown-menu] button').forEach(button => {
        button.addEventListener('click', function(e) {
            // Добавляем эффект нажатия
            button.classList.add('bg-slate-100');

            // Показываем индикатор загрузки
            const originalText = button.innerHTML;
            button.innerHTML = '<span class="flex items-center justify-center gap-2"><svg class="animate-spin h-4 w-4 text-gray-800" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>Выполняется...</span>';

            // Закрываем меню
            const menu = button.closest('[data-dropdown-menu]');
            if (menu) {
                menu.classList.add('hidden');
            }

            // Через небольшую задержку восстанавливаем текст (форма будет отправлена)
            setTimeout(() => {
                button.innerHTML = originalText;
                button.classList.remove('bg-slate-100');
            }, 300);
        });
    });

    // Обработчик уже добавлен выше

    // Используем MutationObserver для отслеживания изменений в DOM и переинициализации обработчиков
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                initTableDropdowns();
            }
        });
    });

    // Начинаем наблюдение за изменениями в DOM
    observer.observe(document.getElementById('tickets-tbody') || document.body, {
        childList: true,
        subtree: true
    });
});
</script>
