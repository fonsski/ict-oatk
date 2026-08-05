<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Штат отдела
    |--------------------------------------------------------------------------
    |
    | Список учётных записей, которые заводит StaffUserSeeder при первом
    | развёртывании. Вход в систему идёт по номеру телефона, поэтому номера
    | обязательно задать в .env — иначе сотрудник не сможет войти.
    |
    | Пароли: если STAFF_DEFAULT_PASSWORD не задан, сидер сгенерирует
    | случайный пароль каждому и один раз выведет его в консоль.
    |
    | role — права в системе (master / technician / admin),
    | position — должность в штатном расписании.
    |
    */

    "members" => [
        [
            "name" => "Хоробров Владислав Дмитриевич",
            "position" => "Заведующий мастерскими",
            "role" => "master",
            "phone" => env("STAFF_KHOROBROV_PHONE"),
            "email" => env("STAFF_KHOROBROV_EMAIL"),
        ],
        [
            "name" => "Синегубов Вячеслав Александрович",
            "position" => "Техник первой категории",
            "role" => "technician",
            "phone" => env("STAFF_SINEGUBOV_PHONE"),
            "email" => env("STAFF_SINEGUBOV_EMAIL"),
        ],
        [
            "name" => "Гребенщиков Кирилл Дмитриевич",
            "position" => "Техник",
            "role" => "technician",
            "phone" => env("STAFF_GREBENSHCHIKOV_PHONE"),
            "email" => env("STAFF_GREBENSHCHIKOV_EMAIL"),
        ],
        [
            "name" => "Кунг Фу Падла",
            "position" => "Техник",
            "role" => "technician",
            "phone" => env("STAFF_KUNGFU_PHONE"),
            "email" => env("STAFF_KUNGFU_EMAIL"),
        ],
    ],

    /*
    | Единый стартовый пароль для всех заводимых учёток. Оставьте пустым,
    | чтобы сидер сгенерировал разные случайные пароли.
    */
    "default_password" => env("STAFF_DEFAULT_PASSWORD"),
];
