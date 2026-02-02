<?php

namespace Database\Seeders;

use App\Models\HomepageFAQ;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class HomepageFAQSeeder extends Seeder
{
    
     * Run the database seeds.

    public function run(): void
    {
        
        $admin = User::whereHas("role", function ($query) {
            $query->where("slug", "admin");
        })->first();

        $faqs = [
            [
                "title" => "Как подать заявку в службу технической поддержки?",
                "excerpt" =>
                    "Пошаговое руководство по созданию заявки в системе",
                "markdown" =>
                    "
                "sort_order" => 1,
                "is_active" => true,
            ],
            [
                "title" => "Где найти номер моей заявки?",
                "excerpt" => "Как найти и отследить статус своей заявки",
                "markdown" =>
                    "
                "sort_order" => 2,
                "is_active" => true,
            ],
            [
                "title" => "Что делать, если компьютер не включается?",
                "excerpt" =>
                    "Базовые шаги диагностики проблем с запуском компьютера",
                "markdown" =>
                    "
                "sort_order" => 3,
                "is_active" => true,
            ],
            [
                "title" => "Как сбросить забытый пароль?",
                "excerpt" =>
                    "Инструкция по восстановлению доступа к учетной записи",
                "markdown" =>
                    "
                "sort_order" => 4,
                "is_active" => true,
            ],
            [
                "title" => "Почему медленно работает интернет?",
                "excerpt" =>
                    "Возможные причины низкой скорости интернета и способы их устранения",
                "markdown" =>
                    "
                "sort_order" => 5,
                "is_active" => true,
            ],
            [
                "title" => "Как подключить принтер к компьютеру?",
                "excerpt" =>
                    "Пошаговая инструкция по подключению и настройке принтера",
                "markdown" =>
                    "
                "sort_order" => 6,
                "is_active" => false, 
            ],
        ];

        foreach ($faqs as $faqData) {
            $faq = new HomepageFAQ();
            $faq->title = $faqData["title"];
            $faq->slug = Str::slug($faqData["title"]);
            $faq->excerpt = $faqData["excerpt"];
            $faq->markdown = $faqData["markdown"];

            
            $content = $faqData["markdown"];
            $content = preg_replace("/
            $content = preg_replace("/
            $content = preg_replace(
",
                '<strong>$1</strong>',
                $content,
            );
", '<em>$1</em>', $content);
            $content = str_replace("\n\n", "</p><p>", $content);
            $content = str_replace("\n", "<br>", $content);
            $content = "<p>" . $content . "</p>";

            $faq->content = $content;
            $faq->sort_order = $faqData["sort_order"];
            $faq->is_active = $faqData["is_active"];
            $faq->author_id = $admin ? $admin->id : null;

            $faq->save();
        }
    }
}
