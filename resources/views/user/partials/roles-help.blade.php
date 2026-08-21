{{--
    Что именно разрешает каждая роль — списком прав, а не абстрактным
    описанием вроде «Работа с заявками», которое ничего не объясняет
    тому, кто выбирает роль для нового сотрудника.

    Список сверен с проверками доступа в коде:
    - routes/web.php — группы маршрутов с CheckRole
    - app/Http/Controllers/TicketController.php — canDelete(), canAssignTo()
--}}
@php
    $rolePermissions = [
        'admin' => [
            'Полный доступ ко всей системе, включая настройки',
            'Заводит, редактирует и удаляет учётные записи сотрудников',
            'Управляет кабинетами, оборудованием и базой знаний',
            'Удаляет заявки и переназначает их любому технику',
        ],
        'master' => [
            'То же, что у администратора, кроме настроек системы',
            'Заводит, редактирует и удаляет учётные записи сотрудников',
            'Управляет кабинетами, оборудованием и базой знаний',
            'Удаляет заявки и переназначает их любому технику',
        ],
        'technician' => [
            'Работает с заявками: берёт в работу, ведёт, закрывает',
            'Смотрит и обновляет карточки оборудования',
            'Может взять свободную заявку себе и отпустить свою обратно',
            'Не может удалить заявку, переназначить чужую или зайти в раздел пользователей',
        ],
    ];
@endphp

<div class="space-y-4">
    @foreach($roles as $role)
        <div class="flex items-start space-x-3">
            <div class="w-2 h-2 bg-blue-500 rounded-full mt-2 flex-shrink-0"></div>
            <div>
                <div class="font-medium text-blue-900">{{ $role->name }}</div>
                @if(isset($rolePermissions[$role->slug]))
                    <ul class="mt-1 space-y-0.5 text-sm text-blue-700 list-disc list-inside">
                        @foreach($rolePermissions[$role->slug] as $permission)
                            <li>{{ $permission }}</li>
                        @endforeach
                    </ul>
                @else
                    {{-- Новая роль, для которой ещё не расписали права здесь. --}}
                    <div class="text-sm text-blue-700">{{ $role->description }}</div>
                @endif
            </div>
        </div>
    @endforeach
</div>
