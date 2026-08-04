<?php

namespace Database\Seeders;

use App\Models\Equipment;
use App\Models\KnowledgeBase;
use App\Models\KnowledgeCategory;
use App\Models\NetworkDiagram;
use App\Models\NetworkLink;
use App\Models\NetworkNode;
use App\Models\Room;
use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Демонстрационные данные для портфолио/скринов:
 * заявки, статьи базы знаний, схема сетевой топологии.
 *
 * Полностью обратим: чтобы снести — DemoShowcaseSeeder::purge() или artisan-команда ниже.
 * Метки: заявки reporter_email '@demo.local', статьи tags содержат 'демо',
 * диаграммы name начинается с 'Демо:'.
 */
class DemoShowcaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::whereHas('role', fn ($q) => $q->where('slug', 'admin'))->first()
            ?? User::first();
        $tech = User::whereHas('role', fn ($q) => $q->where('slug', 'technician'))->first()
            ?? $admin;
        $master = User::whereHas('role', fn ($q) => $q->where('slug', 'master'))->first()
            ?? $tech;

        $rooms = Room::orderBy('id')->take(40)->get();
        $roomId = fn () => optional($rooms->random())->id;

        $this->seedTickets($admin, $tech, $master, $roomId);
        $this->seedKnowledge($admin);
        $this->seedTopology($admin, $rooms);
    }

    private function seedTickets($admin, $tech, $master, $roomId): void
    {
        $reporters = [
            ['Смирнова Анна Петровна', 'a.smirnova@demo.local', '+7 913 000-00-11'],
            ['Кузнецов Игорь Олегович', 'i.kuznetsov@demo.local', '+7 913 000-00-12'],
            ['Волкова Мария Сергеевна', 'm.volkova@demo.local', '+7 913 000-00-13'],
            ['Петров Дмитрий Андреевич', 'd.petrov@demo.local', '+7 913 000-00-14'],
            ['Соколова Елена Викторовна', 'e.sokolova@demo.local', '+7 913 000-00-15'],
            ['Морозов Артём Николаевич', 'a.morozov@demo.local', '+7 913 000-00-16'],
        ];

        $equipIds = Equipment::inRandomOrder()->take(20)->pluck('id')->all();

        // [title, category, priority, status, description]
        $tickets = [
            ['Не включается компьютер в кабинете', 'hardware', 'high', 'open', 'После отключения электричества системный блок не реагирует на кнопку питания. Индикаторы не горят.'],
            ['Не печатает сетевой принтер', 'hardware', 'medium', 'in_progress', 'Принтер отображается офлайн, задания висят в очереди. Пробовали перезагрузить — не помогло.'],
            ['Нет доступа в интернет на этаже', 'network', 'urgent', 'in_progress', 'Пропала сеть в нескольких кабинетах второго этажа, лампочки на коммутаторе мигают.'],
            ['Забыл пароль от учётной записи', 'account', 'low', 'resolved', 'Не могу войти в систему, прошу сбросить пароль.'],
            ['Тормозит компьютер, долго грузится', 'software', 'medium', 'open', 'Windows загружается 5-7 минут, программы открываются с задержкой.'],
            ['Синий экран при работе с 1С', 'software', 'high', 'in_progress', 'Периодически вылетает синий экран во время формирования отчётов в 1С.'],
            ['Не работает проектор в актовом зале', 'hardware', 'high', 'open', 'Проектор включается, но нет сигнала с ноутбука по HDMI.'],
            ['Требуется установить антивирус', 'software', 'low', 'closed', 'На новый компьютер нужно поставить корпоративный антивирус и обновления.'],
            ['Мышь и клавиатура не реагируют', 'hardware', 'medium', 'resolved', 'USB-порты не видят периферию, пробовали разные разъёмы.'],
            ['Медленный Wi-Fi в библиотеке', 'network', 'medium', 'open', 'Слабый сигнал и обрывы соединения у точки доступа в читальном зале.'],
            ['Не открывается общая сетевая папка', 'network', 'medium', 'in_progress', 'Пропал доступ к общему диску, выдаёт ошибку доступа.'],
            ['Заканчивается тонер в МФУ', 'hardware', 'low', 'resolved', 'Печать бледная, требуется замена картриджа.'],
            ['Обновить лицензию Office', 'software', 'low', 'open', 'Всплывает уведомление об истечении лицензии Microsoft Office.'],
            ['Сгорел блок питания', 'hardware', 'urgent', 'in_progress', 'Запах гари от системного блока, компьютер не включается.'],
            ['Настроить почту на новом ПК', 'software', 'low', 'closed', 'Нужно завести корпоративную почту и подписи для нового сотрудника.'],
            ['Не работает интерактивная доска', 'hardware', 'high', 'open', 'Не откликается на касания, калибровка сбилась.'],
            ['Зависает при подключении флешки', 'hardware', 'medium', 'resolved', 'При вставке USB-накопителя система зависает намертво.'],
            ['Пропал звук на рабочем месте', 'hardware', 'low', 'closed', 'Нет звука в колонках, в наушниках тоже тишина.'],
        ];

        $now = now();
        foreach ($tickets as $i => [$title, $cat, $prio, $status, $desc]) {
            [$rName, $rEmail, $rPhone] = $reporters[$i % count($reporters)];
            $createdAt = $now->copy()->subDays(rand(0, 21))->subHours(rand(0, 23));

            $assigned = in_array($status, ['open']) && rand(0, 1)
                ? null
                : ($i % 2 ? $tech->id : $master->id);

            $ticket = Ticket::create([
                'title' => $title,
                'category' => $cat,
                'priority' => $prio,
                'description' => $desc,
                'reporter_name' => $rName,
                'reporter_email' => $rEmail,
                'reporter_phone' => $rPhone,
                'status' => $status,
                'room_id' => $roomId(),
                'assigned_to_id' => $assigned,
                'equipment_id' => $equipIds ? $equipIds[$i % count($equipIds)] : null,
            ]);
            $ticket->created_at = $createdAt;
            $ticket->updated_at = $createdAt->copy()->addHours(rand(1, 40));
            $ticket->save();

            // пара комментариев для «живости»
            if (in_array($status, ['in_progress', 'resolved', 'closed'])) {
                TicketComment::create([
                    'ticket_id' => $ticket->id,
                    'user_id' => $assigned ?? $tech->id,
                    'content' => 'Принял в работу, выезжаю на место для диагностики.',
                    'is_system' => false,
                ]);
            }
            if (in_array($status, ['resolved', 'closed'])) {
                TicketComment::create([
                    'ticket_id' => $ticket->id,
                    'user_id' => $assigned ?? $tech->id,
                    'content' => 'Неисправность устранена, оборудование проверено и работает штатно.',
                    'is_system' => false,
                ]);
            }
        }
    }

    private function seedKnowledge($admin): void
    {
        $cats = KnowledgeCategory::pluck('id', 'slug');
        $catId = fn ($slug) => $cats[$slug] ?? $cats->first();

        $articles = [
            ['Как сбросить пароль от учётной записи', 'account', 'Пошаговая инструкция по самостоятельному восстановлению доступа.', 'пароль,доступ,учётная запись,демо'],
            ['Что делать, если не включается компьютер', 'hardware', 'Базовая диагностика питания и типичные причины неисправности.', 'компьютер,питание,диагностика,демо'],
            ['Настройка сетевого принтера на рабочем месте', 'hardware', 'Добавление сетевого принтера по IP и установка драйверов.', 'принтер,печать,сеть,демо'],
            ['Подключение к корпоративному Wi-Fi', 'network', 'Инструкция по подключению устройств к беспроводной сети колледжа.', 'wi-fi,сеть,интернет,демо'],
            ['Установка и обновление Microsoft Office', 'software', 'Как активировать лицензию и обновить пакет Office.', 'office,лицензия,по,демо'],
            ['Устранение зависаний и медленной работы ПК', 'software', 'Чистка автозагрузки, диска и проверка на вирусы.', 'производительность,windows,оптимизация,демо'],
            ['Доступ к общим сетевым папкам', 'network', 'Подключение сетевого диска и решение ошибок доступа.', 'сеть,папка,доступ,демо'],
        ];

        foreach ($articles as $idx => [$title, $slug, $excerpt, $tags]) {
            $markdown = $this->articleBody($title);
            KnowledgeBase::create([
                'title' => $title,
                'slug' => Str::slug($title) . '-' . ($idx + 1),
                'category_id' => $catId($slug),
                'excerpt' => $excerpt,
                'markdown' => $markdown,
                'content' => $markdown,
                'status' => KnowledgeBase::STATUS_PUBLISHED,
                'tags' => $tags,
                'views_count' => rand(15, 480),
                'author_id' => $admin->id,
                'published_at' => now()->subDays(rand(1, 30)),
            ]);
        }
    }

    private function articleBody(string $title): string
    {
        return "## $title\n\n"
            . "Эта инструкция поможет быстро решить типовую проблему без обращения к специалисту.\n\n"
            . "### Шаги\n\n"
            . "1. Проверьте подключение и перезагрузите устройство.\n"
            . "2. Убедитесь, что все кабели плотно вставлены.\n"
            . "3. Выполните действия из раздела ниже по порядку.\n\n"
            . "### Если не помогло\n\n"
            . "Создайте заявку в системе — укажите кабинет, инвентарный номер оборудования "
            . "и краткое описание проблемы. Специалист свяжется с вами.\n\n"
            . "> Совет: сохраните номер заявки для отслеживания статуса.\n";
    }

    private function seedTopology($admin, $rooms): void
    {
        $diagram = NetworkDiagram::create([
            'name' => 'Демо: Схема сети главного корпуса',
            'description' => 'Магистраль от провайдера до кабинетов: маршрутизатор, ядро и коммутаторы этажей.',
            'author_id' => $admin->id,
        ]);

        $r = fn ($i) => optional($rooms->get($i))->id;

        // [key, label, type, ip, room_index, x, y]
        $nodes = [
            ['inet',  'Интернет (провайдер)', 'internet',    null,          null, 400, 40],
            ['fw',    'Маршрутизатор / FW',   'router',      '10.0.0.1',    null, 400, 160],
            ['core',  'Ядро (L3 switch)',     'switch',      '10.0.0.2',    null, 400, 280],
            ['srv',   'Сервер 1С / файлы',    'server',      '10.0.10.5',   0,    620, 280],
            ['sw1',   'Коммутатор 1 этаж',    'switch',      '10.0.1.1',    1,    180, 420],
            ['sw2',   'Коммутатор 2 этаж',    'switch',      '10.0.2.1',    2,    400, 420],
            ['sw3',   'Коммутатор 3 этаж',    'switch',      '10.0.3.1',    3,    620, 420],
            ['ap1',   'Точка доступа Wi-Fi',  'access_point','10.0.2.50',   4,    400, 540],
            ['ws1',   'Кабинет информатики',  'workstation', '10.0.1.20',   5,    180, 540],
            ['prn1',  'Сетевой принтер',      'printer',     '10.0.3.30',   6,    620, 540],
        ];

        $ids = [];
        foreach ($nodes as [$key, $label, $type, $ip, $ri, $x, $y]) {
            $node = NetworkNode::create([
                'diagram_id' => $diagram->id,
                'label' => $label,
                'type' => $type,
                'ip_address' => $ip,
                'room_id' => $ri === null ? null : $r($ri),
                'pos_x' => $x,
                'pos_y' => $y,
            ]);
            $ids[$key] = $node->id;
        }

        $links = [
            ['inet', 'fw', 'WAN'],
            ['fw', 'core', 'аплинк'],
            ['core', 'srv', 'сервер'],
            ['core', 'sw1', 'этаж 1'],
            ['core', 'sw2', 'этаж 2'],
            ['core', 'sw3', 'этаж 3'],
            ['sw2', 'ap1', 'Wi-Fi'],
            ['sw1', 'ws1', null],
            ['sw3', 'prn1', null],
        ];
        foreach ($links as [$s, $t, $label]) {
            NetworkLink::create([
                'diagram_id' => $diagram->id,
                'source_id' => $ids[$s],
                'target_id' => $ids[$t],
                'label' => $label,
            ]);
        }
    }

    /**
     * Удаление всех демо-данных этого сидера.
     */
    public static function purge(): void
    {
        NetworkDiagram::where('name', 'like', 'Демо:%')->get()->each(function ($d) {
            $d->links()->delete();
            $d->nodes()->delete();
            $d->delete();
        });
        KnowledgeBase::where('tags', 'like', '%демо%')->delete();
        $demoTickets = Ticket::where('reporter_email', 'like', '%@demo.local')->pluck('id');
        TicketComment::whereIn('ticket_id', $demoTickets)->delete();
        Ticket::whereIn('id', $demoTickets)->delete();
    }
}
