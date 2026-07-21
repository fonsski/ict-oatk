@foreach($tickets as $ticket)
    <tr class="hover:bg-slate-50 transition-all duration-300 animate-fade-in" data-ticket-id="{{ $ticket->id }}">
        <td class="px-4 py-4">
            <div class="min-w-0">
                <a href="{{ route('tickets.show', $ticket) }}"
                   class="text-slate-900 font-medium hover:text-blue-600 transition-all duration-300 block"
                   title="{{ $ticket->title }}">
                    <span class="line-clamp-1 break-words">{{ Str::limit($ticket->title, 50) }}</span>
                </a>
                @if($ticket->category)
                    <div class="mt-1">
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            <svg class="w-3 h-3 mr-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M5 3a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2V5a2 2 0 00-2-2H5zM5 11a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2v-2a2 2 0 00-2-2H5zM11 5a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V5zM11 13a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                            </svg>
                            {{ format_ticket_category($ticket->category) }}
                        </span>
                    </div>
                @endif
            </div>
        </td>
        <td class="px-4 py-4">
            <div class="text-sm min-w-0">
                <div class="font-medium text-slate-900 truncate" title="{{ $ticket->reporter_name ?: 'вЂ”' }}">{{ $ticket->reporter_name ?: 'вЂ”' }}</div>
            </div>
        </td>
        <td class="px-4 py-4">
            @php
                $statusColors = [
                    'open' => 'bg-blue-100 text-blue-800',
                    'in_progress' => 'bg-yellow-100 text-yellow-800',
                    'resolved' => 'bg-green-100 text-green-800',
                    'closed' => 'bg-slate-100 text-slate-800'
                ];
                $statusLabels = [
                    'open' => 'РћС‚РєСЂС‹С‚Р°',
                    'in_progress' => 'Р’ СЂР°Р±РѕС‚Рµ',
                    'resolved' => 'Р РµС€РµРЅР°',
                    'closed' => 'Р—Р°РєСЂС‹С‚Р°'
                ];
            @endphp
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$ticket->status] ?? 'bg-slate-100 text-slate-800' }} whitespace-nowrap" 
                  title="РЎС‚Р°С‚СѓСЃ: {{ $statusLabels[$ticket->status] ?? $ticket->status }}">
                {{ $statusLabels[$ticket->status] ?? $ticket->status }}
            </span>
        </td>
        <td class="px-4 py-4">
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $ticket->priority == 'urgent' ? 'bg-red-200 text-red-900' : get_priority_badge_class($ticket->priority) }} whitespace-nowrap" 
                  title="РџСЂРёРѕСЂРёС‚РµС‚: {{ $ticket->priority == 'urgent' ? 'РЎСЂРѕС‡РЅС‹Р№' : format_ticket_priority($ticket->priority) }}">
                {{ $ticket->priority == 'urgent' ? 'РЎСЂРѕС‡РЅС‹Р№' : format_ticket_priority($ticket->priority) }}
            </span>
        </td>
        <td class="px-4 py-4">
            @if($ticket->assignedTo)
                <div class="text-sm min-w-0">
                    <div class="font-medium text-slate-900 truncate" title="{{ $ticket->assignedTo->name }}">{{ $ticket->assignedTo->name }}</div>
                </div>
            @else
                <span class="text-sm text-slate-500 italic">РќРµ РЅР°Р·РЅР°С‡РµРЅРѕ</span>
            @endif
        </td>
        <td class="px-4 py-4 text-center">
            <div class="flex items-center justify-center">
                <div class="relative z-50">
                    <button type="button" class="actions-btn text-slate-500 hover:text-slate-700 p-2 transition-all duration-300 rounded-full hover:bg-slate-100" data-ticket-id="{{ $ticket->id }}" title="Р”РµР№СЃС‚РІРёСЏ">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z"/>
                        </svg>
                    </button>
                    <div class="actions-menu absolute right-0 mt-2 w-48 bg-white rounded-md shadow-xl border border-slate-200 z-50 hidden" data-ticket-id="{{ $ticket->id }}">
                        <div class="py-1">
                            <a href="{{ route('tickets.show', $ticket) }}" class="block px-4 py-3 text-sm text-slate-700 hover:bg-slate-100 transition">РџСЂРѕСЃРјРѕС‚СЂ Р·Р°СЏРІРєРё</a>
                            @if(user_can_manage_tickets())
                                @if($ticket->status !== 'in_progress' && $ticket->status !== 'closed' && !$ticket->assignedTo && Auth::check() && Auth::user()->role && in_array(Auth::user()->role->slug, ['admin', 'master', 'technician']))
                                    <form action="{{ route('tickets.start', $ticket) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="block w-full text-left px-4 py-3 text-sm text-slate-700 hover:bg-slate-100 transition">
                                            Р’Р·СЏС‚СЊ РІ СЂР°Р±РѕС‚Сѓ
                                        </button>
                                    </form>
                                @endif
                                @if($ticket->status === 'in_progress' && $ticket->assignedTo)
                                    <form action="{{ route('tickets.resolve', $ticket) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="block w-full text-left px-4 py-3 text-sm text-slate-700 hover:bg-slate-100 transition">
                                            РћС‚РјРµС‚РёС‚СЊ СЂРµС€С‘РЅРЅРѕР№
                                        </button>
                                    </form>
                                @endif
                                @if($ticket->status === 'resolved' && $ticket->assignedTo)
                                    <form action="{{ route('tickets.close', $ticket) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="block w-full text-left px-4 py-3 text-sm text-slate-700 hover:bg-slate-100 transition">
                                            Р—Р°РєСЂС‹С‚СЊ Р·Р°СЏРІРєСѓ
                                        </button>
                                    </form>
                                @endif
                                @if($ticket->status !== 'closed')
                                    <button type="button" class="block w-full text-left px-4 py-3 text-sm text-slate-700 hover:bg-slate-100 transition" onclick="assignTicket({{ $ticket->id }})">
                                        РќР°Р·РЅР°С‡РёС‚СЊ РёСЃРїРѕР»РЅРёС‚РµР»СЏ
                                    </button>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </td>
    </tr>
@endforeach
