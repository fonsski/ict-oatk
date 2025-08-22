<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Room;

class RoomSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        $rooms = [
            // Главное здание, 1 этаж
            [
                "number" => "101",
                "name" => "Компьютерный класс №1",
                "description" =>
                    "Основной компьютерный класс с 20 рабочими местами",
                "floor" => "1",
                "building" => "Главное здание",
                "capacity" => 20,
                "type" => "computer_lab",
                "is_active" => true,
                "status" => "available",
                "responsible_person" => "Иванов И.И.",
                "phone" => "+7 (495) 123-45-67",
            ],
            [
                "number" => "102",
                "name" => "Лекционная аудитория",
                "description" => "Большая аудитория для лекций",
                "floor" => "1",
                "building" => "Главное здание",
                "capacity" => 50,
                "type" => "auditorium",
                "is_active" => true,
                "status" => "available",
                "responsible_person" => "Петров П.П.",
                "phone" => "+7 (495) 123-45-68",
            ],
            [
                "number" => "103",
                "name" => "Лаборатория физики",
                "description" =>
                    "Физическая лаборатория с экспериментальным оборудованием",
                "floor" => "1",
                "building" => "Главное здание",
                "capacity" => 15,
                "type" => "laboratory",
                "is_active" => true,
                "status" => "available",
                "responsible_person" => "Сидоров С.С.",
                "phone" => "+7 (495) 123-45-69",
            ],

            // Главное здание, 2 этаж
            [
                "number" => "201",
                "name" => "Компьютерный класс №2",
                "description" => "Дополнительный компьютерный класс",
                "floor" => "2",
                "building" => "Главное здание",
                "capacity" => 18,
                "type" => "computer_lab",
                "is_active" => true,
                "status" => "available",
                "responsible_person" => "Козлов К.К.",
                "phone" => "+7 (495) 123-45-70",
            ],
            [
                "number" => "202",
                "name" => "Учебный класс математики",
                "description" => "Класс для изучения математических дисциплин",
                "floor" => "2",
                "building" => "Главное здание",
                "capacity" => 25,
                "type" => "classroom",
                "is_active" => true,
                "status" => "available",
                "responsible_person" => "Николаев Н.Н.",
                "phone" => "+7 (495) 123-45-71",
            ],
            [
                "number" => "203",
                "name" => "Библиотека",
                "description" => "Читальный зал библиотеки",
                "floor" => "2",
                "building" => "Главное здание",
                "capacity" => 30,
                "type" => "library",
                "is_active" => true,
                "status" => "available",
                "responsible_person" => "Морозова М.М.",
                "phone" => "+7 (495) 123-45-72",
            ],

            // Главное здание, 3 этаж
            [
                "number" => "301",
                "name" => "Конференц-зал",
                "description" =>
                    "Большой зал для проведения конференций и собраний",
                "floor" => "3",
                "building" => "Главное здание",
                "capacity" => 100,
                "type" => "conference",
                "is_active" => true,
                "status" => "available",
                "responsible_person" => "Волков В.В.",
                "phone" => "+7 (495) 123-45-73",
            ],
            [
                "number" => "302",
                "name" => "Кабинет директора",
                "description" => "Рабочий кабинет директора учебного заведения",
                "floor" => "3",
                "building" => "Главное здание",
                "capacity" => 5,
                "type" => "office",
                "is_active" => true,
                "status" => "occupied",
                "responsible_person" => "Директор",
                "phone" => "+7 (495) 123-45-74",
            ],

            // Лабораторный корпус, 1 этаж
            [
                "number" => "Л101",
                "name" => "Лаборатория химии",
                "description" => "Химическая лаборатория с вытяжными шкафами",
                "floor" => "1",
                "building" => "Лабораторный корпус",
                "capacity" => 12,
                "type" => "laboratory",
                "is_active" => true,
                "status" => "available",
                "responsible_person" => "Лебедев Л.Л.",
                "phone" => "+7 (495) 123-45-75",
            ],
            [
                "number" => "Л102",
                "name" => "Мастерская",
                "description" => "Учебно-производственная мастерская",
                "floor" => "1",
                "building" => "Лабораторный корпус",
                "capacity" => 10,
                "type" => "workshop",
                "is_active" => true,
                "status" => "maintenance",
                "responsible_person" => "Кузнецов К.К.",
                "phone" => "+7 (495) 123-45-76",
            ],

            // Спортивный корпус
            [
                "number" => "С001",
                "name" => "Спортивный зал",
                "description" =>
                    "Большой спортивный зал для игровых видов спорта",
                "floor" => "1",
                "building" => "Спортивный корпус",
                "capacity" => 40,
                "type" => "gym",
                "is_active" => true,
                "status" => "available",
                "responsible_person" => "Спортсменов С.С.",
                "phone" => "+7 (495) 123-45-77",
            ],
        ];

        foreach ($rooms as $roomData) {
            Room::create($roomData);
        }
    }
}
