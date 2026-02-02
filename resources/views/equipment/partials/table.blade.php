<div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Инв. номер / Название
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Статус
                </th>
                <!-- Колонка локации удалена, так как она не используется в системе -->
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Категория
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Кабинет
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Гарантия
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Последнее обслуживание
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Действия
                </th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            @forelse($items as $item)
            <tr>
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm font-medium text-gray-900">{{ $item->inventory_number }} @if($item->name) — <span class="text-gray-700">{{ $item->name }}</span>@endif</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                            {{ $item->status->slug === 'working' ? 'bg-green-100 text-green-800' :
                               ($item->status->slug === 'in_service' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                        {{ $item->status->name }}
                    </span>
                </td>
                <!-- Ячейка локации удалена, так как она не используется в системе -->
                <td class="px-6 py-4 whitespace-nowrap">
                    {{ $item->category ? $item->category->name : 'Не указана' }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    {{ $item->room ? $item->room->number . ' - ' . $item->room->name : 'Не указан' }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    @if($item->has_warranty)
                        @if($item->warranty_end_date >= now())
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                До {{ $item->warranty_end_date->format('d.m.Y') }}
                            </span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                Истекла {{ $item->warranty_end_date->format('d.m.Y') }}
                            </span>
                        @endif
                    @else
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                            Нет
                        </span>
                    @endif
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    {{ $item->last_service_date ? $item->last_service_date->format('d.m.Y') : 'Нет данных' }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                    <a href="{{ route('equipment.show', $item) }}" class="text-blue-600 hover:text-blue-900 mr-3">
                        Просмотр
                    </a>
                    @if(optional(auth()->user())->role && in_array(optional(auth()->user()->role)->slug, ['admin','master']))
                    <a href="{{ route('equipment.edit', $item) }}" class="text-blue-600 hover:text-blue-900 mr-3">
                        Изменить
                    </a>
                    <form action="{{ route('equipment.destroy', $item) }}" method="POST" class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-red-600 hover:text-red-900" onclick="return confirm('Вы уверены?')">
                            Удалить
                        </button>
                    </form>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                    <div class="flex flex-col items-center">
                        <svg class="w-12 h-12 text-gray-400 mb-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                            <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
                        </svg>
                        <h3 class="text-lg font-medium text-gray-900 mb-1">Оборудование не найдено</h3>
                        <p class="text-gray-500">Попробуйте изменить параметры поиска</p>
                    </div>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<!-- Pagination -->
@if($items->hasPages())
<div class="mt-4">
    {{ $items->links() }}
</div>
@endif
