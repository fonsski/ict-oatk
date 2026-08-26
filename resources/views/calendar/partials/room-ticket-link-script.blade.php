@once
@push('scripts')
<script>
    // Связывает выбор кабинета и заявки: заявки выбранного кабинета
    // поднимаются в списке наверх, свободное поле заявки автоматически
    // заполняется подходящей, а рядом показывается кабинет выбранной заявки.
    function linkRoomTicket(roomSelId, ticketSelId, badgeId) {
        const room = document.getElementById(roomSelId);
        const ticket = document.getElementById(ticketSelId);
        const badge = document.getElementById(badgeId);
        if (!room || !ticket) return;

        function updateBadge() {
            if (!badge) return;
            const opt = ticket.selectedOptions[0];
            const r = opt ? opt.dataset.room : '';
            badge.textContent = r ? ('Кабинет заявки: ' + r) : '';
        }

        function prioritize(roomId) {
            const rest = Array.from(ticket.options).slice(1); // без «нет»
            rest.sort((a, b) => {
                const am = a.dataset.roomId === roomId ? 0 : 1;
                const bm = b.dataset.roomId === roomId ? 0 : 1;
                return am - bm;
            });
            rest.forEach((o) => ticket.appendChild(o));
        }

        room.addEventListener('change', () => {
            const roomId = room.value;
            if (roomId) {
                prioritize(roomId);
                // Заявку подставляем, только если поле ещё пустое — выбор
                // «нет» пользователь всегда может вернуть.
                if (!ticket.value) {
                    const match = Array.from(ticket.options).find((o) => o.dataset.roomId === roomId);
                    if (match) ticket.value = match.value;
                }
            }
            updateBadge();
        });

        ticket.addEventListener('change', updateBadge);
        updateBadge();
    }
</script>
@endpush
@endonce
