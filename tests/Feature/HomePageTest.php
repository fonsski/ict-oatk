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

    private function seedFaqs(): void
    {
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
    }

    public function test_guest_sees_active_faq_entries(): void
    {
        $this->seedFaqs();

        $this->get('/')
            ->assertOk()
            ->assertSee('Как подать заявку?', false)
            ->assertDontSee('Скрытый вопрос', false);
    }

    public function test_staff_sees_faq_entries(): void
    {
        $this->seedFaqs();

        $technician = User::factory()->withRole('technician')->create();

        $this->actingAs($technician)
            ->get('/')
            ->assertOk()
            ->assertSee('Как подать заявку?', false);
    }

    public function test_guest_can_open_faq_detail_page(): void
    {
        $this->seedFaqs();

        $this->get(route('homepage-faq.show', 'kak-podat-zaiavku'))->assertOk();
    }

    public function test_static_pages_are_available(): void
    {
        $this->get(route('terms'))->assertOk();
        $this->get(route('privacy'))->assertOk();
    }

    public function test_login_page_is_available_to_guests(): void
    {
        $this->get(route('login'))->assertOk();
    }

    public function test_registration_route_no_longer_exists(): void
    {
        // Саморегистрация удалена: аккаунты заводит только администратор.
        $this->assertFalse(app('router')->has('register'));
    }
}
