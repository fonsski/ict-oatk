<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Room;

class RoomSeeder extends Seeder
{
    
     * Run the database seeder.

    public function run(): void
    {
        $rooms = [
            
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
            ],

            
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
            ],

            
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
            ],

            
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
            ],

            
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
            ],
        ];

        foreach ($rooms as $roomData) {
            Room::create($roomData);
        }
    }
}
