@foreach($tickets as $ticket)
    <tr class="hover:bg-slate-50 transition-colors duration-200" data-ticket-id="{{ $ticket->id }}">
        <td class="px-6 py-4">
            <div>
                <a href="{{ route('tickets.show', $ticket) }}"
                   class="text-slate-900 font-medium hover:text-blue-600 transition-colors duration-200">
                    {{ $ticket->title }}
                </a>
                <p class="text-sm text-slate-600 mt-1 line-clamp-2">
                    {{ Str::limit($ticket->description, 80) }}
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
                <div class="text-slate-600">{{ $ticket->reporter_email ?: '—' }}</div>
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
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $ticket->priority == 'urgent' ? 'bg-red-200 text-red-900' : get_priority_badge_class($ticket->priority) }}">
                {{ $ticket->priority == 'urgent' ? 'Срочный' : format_ticket_priority($ticket->priority) }}
            </span>
        </td>
        <td class="px-6 py-4">
            @if($ticket->assignedTo)
                <div class="flex items-center">
                    <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center text-white text-sm font-medium mr-2">
                        {{ substr($ticket->assignedTo->name, 0, 1) }}
                    </div>
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
                   class="text-blue-600 hover:text-blue-700 font-medium text-sm">
                    Просмотр
                </a>
                @if(user_can_manage_tickets())
                    <div class="relative" data-dropdown>
                        <button class="text-slate-400 hover:text-slate-600 p-1" data-dropdown-toggle>
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z"/>
                            </svg>
                        </button>
                        <div class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg border border-slate-200 z-10 hidden" data-dropdown-menu>
                            <div class="py-1">
                                @if($ticket->status !== 'in_progress')
                                    <form action="{{ route('tickets.start', $ticket) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-slate-700 hover:bg-slate-100">
                                            Взять в работу
                                        </button>
                                    </form>
                                @endif
                                @if($ticket->status === 'in_progress')
                                    <form action="{{ route('tickets.resolve', $ticket) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-slate-700 hover:bg-slate-100">
                                            Отметить решённой
                                        </button>
                                    </form>
                                @endif
                                @if($ticket->status === 'resolved')
                                    <form action="{{ route('tickets.close', $ticket) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-slate-700 hover:bg-slate-100">
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
document.addEventListener('DOMContentLoaded', function() {
    // Обработка выпадающих меню в таблице
    document.querySelectorAll('[data-dropdown]').forEach(function(dropdown) {
        const toggle = dropdown.querySelector('[data-dropdown-toggle]');
        const menu = dropdown.querySelector('[data-dropdown-menu]');

        if (toggle && menu) {
            toggle.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();

                // Закрыть все другие меню
                document.querySelectorAll('[data-dropdown-menu]').forEach(function(otherMenu) {
                    if (otherMenu !== menu) {
                        otherMenu.classList.add('hidden');
                    }
                });

                // Переключить текущее меню
                menu.classList.toggle('hidden');
            });
        }
    });

    // Закрытие меню при клике вне его
    document.addEventListener('click', function(e) {
        if (!e.target.closest('[data-dropdown]')) {
            document.querySelectorAll('[data-dropdown-menu]').forEach(function(menu) {
                menu.classList.add('hidden');
            });
        }
    });
});
</script>
