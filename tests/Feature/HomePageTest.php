<?php

namespace Tests\Feature;

use App\Models\HomepageFAQ;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Smoke-тесты публичных страниц, доступных без авторизации.
 */
class HomePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_is_available_to_guests(): void
    {
        $this->get('/')->assertOk();
    }

    public function test_home_page_shows_active_faq_entries_to_staff(): void
    {
        // Блок FAQ на главной обёрнут в @auth + @unless(user_has_role('user')),
        // то есть виден только персоналу — гость и обычный пользователь его не получают.
        $technician = User::factory()->withRole('technician')->create();

        HomepageFAQ::create([
            'title' => 'Как подать заявку?',
            'content' => 'Нажмите кнопку «Создать заявку» в верхнем меню.',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        HomepageFAQ::create([
            'title' => 'Скрытый вопрос',
            'content' => 'Этот ответ не должен попасть на главную.',
            'is_active' => false,
            'sort_order' => 2,
        ]);

        $this->actingAs($technician)
            ->get('/')
            ->assertOk()
            ->assertSee('Как подать заявку?', false)
            ->assertDontSee('Скрытый вопрос', false);
    }

    public function test_faq_block_is_hidden_from_regular_users(): void
    {
        HomepageFAQ::create([
            'title' => 'Как подать заявку?',
            'content' => 'Нажмите кнопку «Создать заявку» в верхнем меню.',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $user = User::factory()->withRole('user')->create();

        $this->actingAs($user)
            ->get('/')
            ->assertOk()
            ->assertDontSee('Как подать заявку?', false);
    }

    public function test_static_pages_are_available(): void
    {
        $this->get(route('terms'))->assertOk();
        $this->get(route('privacy'))->assertOk();
    }

    public function test_login_and_register_pages_are_available_to_guests(): void
    {
        $this->get(route('login'))->assertOk();
        $this->get(route('register'))->assertOk();
    }
}
