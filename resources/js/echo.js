import Echo from "laravel-echo";
import Pusher from "pusher-js";

// Reverb использует протокол Pusher, поэтому Echo работает через pusher-js.
window.Pusher = Pusher;

// Поднимаем Echo только когда Reverb реально настроен. Без ключа (или при
// VITE_ENABLED=false) браузер иначе бесконечно пытается открыть WebSocket к
// несуществующему серверу и засыпает консоль ошибками. Модули, слушающие
// window.Echo, сами проверяют его наличие и молча выключаются.
const reverbKey = import.meta.env.VITE_REVERB_APP_KEY;
const realtimeEnabled = import.meta.env.VITE_ENABLED !== "false";

if (reverbKey && realtimeEnabled) {
    window.Echo = new Echo({
        broadcaster: "reverb",
        key: reverbKey,
        wsHost: import.meta.env.VITE_REVERB_HOST,
        wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
        wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
        forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? "https") === "https",
        enabledTransports: ["ws", "wss"],
    });
}
