// Подписка на приватные каналы Reverb. Модуль разворачивает входящие
// события в глобальные CustomEvent'ы, чтобы страницы могли реагировать,
// ничего не зная про Echo:
//   - realtime:tickets      — изменения по заявкам (для доски на главной)
//   - realtime:notification — персональное уведомление (для колокольчика)

document.addEventListener("DOMContentLoaded", () => {
    if (typeof window.Echo === "undefined") {
        return;
    }

    // Канал персонала: любые изменения по заявкам.
    const emitTickets = (event) =>
        window.dispatchEvent(
            new CustomEvent("realtime:tickets", { detail: event }),
        );

    window.Echo.private("staff")
        .listen(".ticket.created", emitTickets)
        .listen(".ticket.status", emitTickets)
        .listen(".ticket.assigned", emitTickets)
        .listen(".ticket.comment", emitTickets);

    // Персональный канал: уведомления текущего пользователя.
    if (window.authUserId) {
        window.Echo.private(`user.${window.authUserId}`).listen(
            ".notification.created",
            (event) => {
                window.dispatchEvent(
                    new CustomEvent("realtime:notification", { detail: event }),
                );

                if (typeof window.showInfo === "function") {
                    window.showInfo(event.message || "Новое уведомление", {
                        title: event.title || "Уведомление",
                    });
                }
            },
        );
    }
});
