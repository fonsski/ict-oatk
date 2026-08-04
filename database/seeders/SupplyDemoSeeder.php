<?php

namespace Database\Seeders;

use App\Models\Consumable;
use App\Models\ConsumableWriteOff;
use App\Models\Document;
use App\Models\Equipment;
use App\Models\EquipmentCategory;
use App\Models\KnowledgeBase;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Room;
use App\Models\User;
use App\Models\WriteOff;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Демо-данные снабжения: расходники с движениями остатка, проведённая
 * закупка, акт списания оборудования и привязка статей базы знаний.
 *
 * Идемпотентен: повторный запуск не плодит дубли (ключи — номера актов
 * и наименования расходников).
 */
class SupplyDemoSeeder extends Seeder
{
    public function run(): void
    {
        $author = User::whereHas("role", fn($q) => $q->whereIn("slug", ["admin", "master"]))
            ->first() ?? User::first();

        if (!$author) {
            $this->command->warn("SupplyDemoSeeder: нет пользователей, пропускаем");
            return;
        }

        $consumables = $this->seedConsumables();
        $this->seedPurchase($author, $consumables);
        $this->seedConsumableWriteOff($author, $consumables);
        $this->seedEquipmentWriteOff($author);
        $this->seedKnowledgeLinks();

        $this->command->info("SupplyDemoSeeder: демо-данные снабжения готовы");
    }

    /**
     * @return array<string, Consumable>
     */
    private function seedConsumables(): array
    {
        $room = Room::query()->first();

        $definitions = [
            ["Картридж HP CF226A", "Картриджи", 12, 5],
            ["Тонер Kyocera TK-1170", "Картриджи", 3, 5],   // ниже минимума — для подсветки
            ["Патч-корд UTP cat.5e 2 м", "Кабели", 40, 10],
            ["Батарейка AA", "Комплектующие", 60, 20],
            ["Термопаста КПТ-8", "Комплектующие", 4, 2],
        ];

        $consumables = [];
        foreach ($definitions as [$name, $category, $quantity, $minQuantity]) {
            $consumables[$name] = Consumable::firstOrCreate(
                ["name" => $name],
                [
                    "category" => $category,
                    "unit" => "шт",
                    "quantity" => $quantity,
                    "min_quantity" => $minQuantity,
                    "room_id" => $room?->id,
                ],
            );
        }

        return $consumables;
    }

    private function seedPurchase(User $author, array $consumables): void
    {
        if (Purchase::where("number", "ЗАК-2026-0001")->exists()) {
            return;
        }

        DB::transaction(function () use ($author, $consumables) {
            $purchase = Purchase::create([
                "number" => "ЗАК-2026-0001",
                "date" => now()->subMonth(),
                "supplier" => "ООО «Комус»",
                "created_by_user_id" => $author->id,
                "status" => Purchase::STATUS_DRAFT,
                "comment" => "Плановая поставка расходных материалов",
            ]);

            $cartridge = $consumables["Картридж HP CF226A"];
            $purchase->items()->create([
                "item_type" => PurchaseItem::TYPE_CONSUMABLE,
                "consumable_id" => $cartridge->id,
                "name" => $cartridge->name,
                "quantity" => 10,
                "unit_price" => 2450.00,
                "sum" => 24500.00,
            ]);

            $monitors = $purchase->items()->create([
                "item_type" => PurchaseItem::TYPE_EQUIPMENT,
                "equipment_category_id" => EquipmentCategory::where("name", "Монитор")->value("id"),
                "name" => "Монитор Dell P2422H",
                "quantity" => 2,
                "unit_price" => 18900.00,
                "sum" => 37800.00,
            ]);

            $purchase->recalculateTotal();

            // Инвентарные номера обычно вводит человек на шаге проведения —
            // для демо берём свободные номера из того же диапазона.
            $purchase->post([
                $monitors->id => $this->freeInventoryNumbers(2),
            ]);
        });
    }

    /**
     * Подобрать свободные инвентарные номера для демо-закупки.
     *
     * @return array<int, string>
     */
    private function freeInventoryNumbers(int $count): array
    {
        $numbers = [];
        $candidate = 9900000001;

        while (count($numbers) < $count) {
            if (!Equipment::where("inventory_number", (string) $candidate)->exists()) {
                $numbers[] = (string) $candidate;
            }
            $candidate++;
        }

        return $numbers;
    }

    private function seedConsumableWriteOff(User $author, array $consumables): void
    {
        if (ConsumableWriteOff::where("number", "СП-2026-0001")->exists()) {
            return;
        }

        DB::transaction(function () use ($author, $consumables) {
            $writeOff = ConsumableWriteOff::create([
                "number" => "СП-2026-0001",
                "written_off_at" => now()->subWeek(),
                "reason" => "Израсходовано при плановом обслуживании",
                "written_off_by_user_id" => $author->id,
            ]);

            foreach ([
                ["Картридж HP CF226A", 3],
                ["Патч-корд UTP cat.5e 2 м", 6],
            ] as [$name, $quantity]) {
                $consumables[$name]->recordOutcome($quantity, [
                    "reason" => "Массовое списание {$writeOff->number}",
                    "consumable_write_off_id" => $writeOff->id,
                    "moved_by_user_id" => $author->id,
                    "moved_at" => $writeOff->written_off_at,
                ]);
            }
        });
    }

    private function seedEquipmentWriteOff(User $author): void
    {
        if (WriteOff::where("number", "АКТ-2026-0001")->exists()) {
            return;
        }

        $equipment = Equipment::whereNull("write_off_id")->limit(3)->get();
        if ($equipment->isEmpty()) {
            return;
        }

        DB::transaction(function () use ($author, $equipment) {
            $writeOff = WriteOff::create([
                "number" => "АКТ-2026-0001",
                "date" => now()->subDays(10),
                "reason" => "Физический износ, неремонтопригодность",
                "basis" => "Приказ № 42 от " . now()->subDays(14)->format("d.m.Y"),
                "created_by_user_id" => $author->id,
                "status" => WriteOff::STATUS_DRAFT,
            ]);

            foreach ($equipment as $item) {
                $writeOff->items()->create(["equipment_id" => $item->id]);
            }

            $writeOff->post();
        });
    }

    private function seedKnowledgeLinks(): void
    {
        $article = KnowledgeBase::query()->first();
        if (!$article) {
            return;
        }

        Equipment::whereNull("write_off_id")
            ->limit(2)
            ->get()
            ->each(fn(Equipment $item) => $item->knowledgeArticles()->syncWithoutDetaching([$article->id]));
    }
}
