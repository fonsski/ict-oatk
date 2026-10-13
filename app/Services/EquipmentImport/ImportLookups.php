<?php

namespace App\Services\EquipmentImport;

use App\Models\Equipment;
use App\Models\EquipmentCategory;
use App\Models\EquipmentStatus;
use App\Models\OperatingSystem;
use App\Models\Room;
use Illuminate\Support\Collection;

/**
 * Справочники системы, загруженные один раз на весь импорт, чтобы проверка
 * тысячи строк не делала тысячу запросов. Названия сравниваются без учёта
 * регистра и пробелов по краям.
 */
final class ImportLookups
{
    /** @var Collection<string, EquipmentCategory> */
    private Collection $categories;

    /** @var Collection<int, EquipmentCategory> */
    private Collection $categoriesById;

    /** @var Collection<string, EquipmentStatus> */
    private Collection $statuses;

    /** @var Collection<string, Room> */
    private Collection $rooms;

    /** @var Collection<string, OperatingSystem> */
    private Collection $operatingSystems;

    /** @var Collection<string, Equipment> */
    private Collection $equipment;

    /** @var Collection<string, int> */
    private Collection $accountingOwners;

    public function __construct()
    {
        $this->categoriesById = EquipmentCategory::all()->keyBy("id");
        $this->categories = $this->categoriesById->keyBy(fn ($c) => self::key($c->name));

        $this->statuses = new Collection();
        foreach (EquipmentStatus::all() as $status) {
            $this->statuses[self::key($status->slug)] = $status;
            $this->statuses[self::key($status->name)] = $status;
        }

        $this->rooms = Room::all(["id", "number"])->keyBy(fn ($r) => self::key($r->number));

        $this->operatingSystems = new Collection();
        foreach (OperatingSystem::all(["id", "name", "slug"]) as $os) {
            $this->operatingSystems[self::key($os->name)] = $os;
            $this->operatingSystems[self::key($os->slug)] = $os;
        }

        $this->equipment = Equipment::withTrashed()
            ->get([
                "id", "inventory_number", "accounting_number", "category_id",
                "operating_system_id", "has_warranty", "warranty_end_date", "deleted_at",
            ])
            ->keyBy("inventory_number");

        $this->accountingOwners = $this->equipment
            ->filter(fn ($e) => $e->accounting_number !== null)
            ->mapWithKeys(fn ($e) => [self::key($e->accounting_number) => $e->id]);
    }

    public static function key(string $value): string
    {
        return mb_strtolower(trim($value));
    }

    public function category(string $name): ?EquipmentCategory
    {
        return $this->categories->get(self::key($name));
    }

    public function categoryById(?int $id): ?EquipmentCategory
    {
        return $id === null ? null : $this->categoriesById->get($id);
    }

    public function status(string $slugOrName): ?EquipmentStatus
    {
        return $this->statuses->get(self::key($slugOrName));
    }

    /**
     * Допустимые slug статусов — для подсказки в сообщении об ошибке.
     *
     * @return list<string>
     */
    public function statusSlugs(): array
    {
        return $this->statuses->pluck("slug")->unique()->values()->all();
    }

    public function room(string $number): ?Room
    {
        return $this->rooms->get(self::key($number));
    }

    public function operatingSystem(string $nameOrSlug): ?OperatingSystem
    {
        return $this->operatingSystems->get(self::key($nameOrSlug));
    }

    public function equipment(string $inventoryNumber): ?Equipment
    {
        return $this->equipment->get($inventoryNumber);
    }

    /**
     * Id оборудования, у которого уже занят этот учётный номер.
     */
    public function accountingOwner(string $accountingNumber): ?int
    {
        return $this->accountingOwners->get(self::key($accountingNumber));
    }
}
