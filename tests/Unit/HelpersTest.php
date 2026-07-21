<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Тесты чистых функций из app/helpers.php.
 * База данных и приложение не поднимаются — функции подключены
 * через секцию autoload.files в composer.json.
 */
class HelpersTest extends TestCase
{
    public function test_truncate_text_keeps_cyrillic_intact(): void
    {
        $result = truncate_text('Привет мир', 6);

        $this->assertSame('Привет...', $result);
        $this->assertTrue(
            mb_check_encoding($result, 'UTF-8'),
            'Обрезка не должна разрывать многобайтовые символы',
        );
    }

    public function test_truncate_text_counts_characters_not_bytes(): void
    {
        // 10 кириллических символов — это 20 байт. Байтовый strlen счёл бы
        // строку длиннее лимита и обрезал бы её без необходимости.
        $this->assertSame('Приветмир!', truncate_text('Приветмир!', 10));
    }

    public function test_truncate_text_returns_short_text_unchanged(): void
    {
        $this->assertSame('коротко', truncate_text('коротко', 100));
    }

    public function test_truncate_text_supports_custom_suffix(): void
    {
        $this->assertSame('Прив→', truncate_text('Привет', 4, '→'));
    }

    #[DataProvider('phoneProvider')]
    public function test_format_phone_normalizes_russian_numbers(
        ?string $input,
        ?string $expected,
    ): void {
        $this->assertSame($expected, format_phone($input));
    }

    public static function phoneProvider(): array
    {
        return [
            'ведущая восьмёрка' => ['89001234567', '+7 (900) 123-45-67'],
            'ведущая семёрка' => ['79001234567', '+7 (900) 123-45-67'],
            'десять цифр' => ['9001234567', '+7 (900) 123-45-67'],
            'уже отформатирован' => [
                '+7 (900) 123-45-67',
                '+7 (900) 123-45-67',
            ],
            'null' => [null, null],
            'пустая строка' => ['', null],
        ];
    }

    public function test_clean_phone_strips_formatting(): void
    {
        $this->assertSame('+79001234567', clean_phone('+7 (900) 123-45-67'));
    }

    public function test_clean_phone_returns_null_for_empty_input(): void
    {
        $this->assertNull(clean_phone(null));
        $this->assertNull(clean_phone(''));
    }

    public function test_format_ticket_status_maps_known_statuses(): void
    {
        $this->assertSame('Открыта', format_ticket_status('open'));
        $this->assertSame('В работе', format_ticket_status('in_progress'));
        $this->assertSame('Решена', format_ticket_status('resolved'));
        $this->assertSame('Закрыта', format_ticket_status('closed'));
    }

    public function test_format_ticket_status_falls_back_to_raw_value(): void
    {
        $this->assertSame('нечто', format_ticket_status('нечто'));
    }

    public function test_format_ticket_priority_and_category_mapping(): void
    {
        $this->assertSame('Срочный', format_ticket_priority('urgent'));
        $this->assertSame('Оборудование', format_ticket_category('hardware'));
    }

    public function test_badge_classes_have_defaults_for_unknown_values(): void
    {
        $this->assertSame(
            'bg-slate-100 text-slate-800',
            get_status_badge_class('неизвестно'),
        );
        $this->assertSame(
            'bg-slate-100 text-slate-800',
            get_priority_badge_class('неизвестно'),
        );
    }

    public function test_format_datetime_renders_dash_for_null(): void
    {
        $this->assertSame('—', format_datetime(null));
        $this->assertSame('—', format_date(null));
    }

    public function test_format_datetime_formats_string_input(): void
    {
        $this->assertSame('05.03.2025 14:30', format_datetime('2025-03-05 14:30:00'));
        $this->assertSame('05.03.2025', format_date('2025-03-05 14:30:00'));
    }
}
