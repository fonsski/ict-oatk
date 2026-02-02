<?php

namespace App\Services;

use App\Models\Role;
use App\Models\Location;
use App\Models\Room;
use App\Models\Equipment;
use App\Models\EquipmentCategory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CacheService
{
    
     * Ключи кеша

    const CACHE_KEYS = [
        'roles' => 'roles_list',
        'locations' => 'locations_list',
        'rooms' => 'rooms_list',
        'equipment_categories' => 'equipment_categories_list',
        'active_equipment' => 'active_equipment_list',
        'assignable_users' => 'assignable_users_list',
        'ticket_categories' => 'ticket_categories_list',
        'ticket_priorities' => 'ticket_priorities_list',
        'ticket_statuses' => 'ticket_statuses_list',
    ];

    
     * Время жизни кеша (в секундах)

    const CACHE_TTL = [
        'roles' => 3600, 
        'locations' => 3600, 
        'rooms' => 1800, 
        'equipment_categories' => 3600, 
        'active_equipment' => 1800, 
        'assignable_users' => 1800, 
        'ticket_categories' => 7200, 
        'ticket_priorities' => 7200, 
        'ticket_statuses' => 7200, 
    ];

    
     * Получить список ролей

    public function getRoles()
    {
        return Cache::remember(
            self::CACHE_KEYS['roles'],
            self::CACHE_TTL['roles'],
            function () {
                return Role::select('id', 'name', 'slug')
                    ->orderBy('name')
                    ->get();
            }
        );
    }

    
     * Получить список местоположений

    public function getLocations()
    {
        return Cache::remember(
            self::CACHE_KEYS['locations'],
            self::CACHE_TTL['locations'],
            function () {
                return Location::select('id', 'name')
                    ->orderBy('name')
                    ->get();
            }
        );
    }

    
     * Получить список активных кабинетов

    public function getActiveRooms()
    {
        return Cache::remember(
            self::CACHE_KEYS['rooms'],
            self::CACHE_TTL['rooms'],
            function () {
                return Room::active()
                    ->select('id', 'number', 'name', 'type', 'building', 'floor')
                    ->orderBy('number')
                    ->get();
            }
        );
    }

    
     * Получить список категорий оборудования

    public function getEquipmentCategories()
    {
        return Cache::remember(
            self::CACHE_KEYS['equipment_categories'],
            self::CACHE_TTL['equipment_categories'],
            function () {
                return EquipmentCategory::select('id', 'name', 'description')
                    ->orderBy('name')
                    ->get();
            }
        );
    }

    
     * Получить список активного оборудования

    public function getActiveEquipment()
    {
        return Cache::remember(
            self::CACHE_KEYS['active_equipment'],
            self::CACHE_TTL['active_equipment'],
            function () {
                return Equipment::active()
                    ->with(['category', 'room'])
                    ->select('id', 'name', 'model', 'serial_number', 'category_id', 'room_id')
                    ->orderBy('name')
                    ->get();
            }
        );
    }

    
     * Получить список пользователей, которым можно назначать заявки

    public function getAssignableUsers()
    {
        return Cache::remember(
            self::CACHE_KEYS['assignable_users'],
            self::CACHE_TTL['assignable_users'],
            function () {
                return \App\Models\User::whereHas('role', function ($q) {
                    $q->whereIn('slug', ['admin', 'master', 'technician']);
                })
                ->where('is_active', true)
                ->select('id', 'name', 'phone')
                ->orderBy('name')
                ->get();
            }
        );
    }

    
     * Получить список категорий заявок

    public function getTicketCategories()
    {
        return Cache::remember(
            self::CACHE_KEYS['ticket_categories'],
            self::CACHE_TTL['ticket_categories'],
            function () {
                return [
                    'hardware' => 'Оборудование',
                    'software' => 'Программное обеспечение',
                    'network' => 'Сеть и интернет',
                    'account' => 'Учетная запись',
                    'other' => 'Другое',
                ];
            }
        );
    }

    
     * Получить список приоритетов заявок

    public function getTicketPriorities()
    {
        return Cache::remember(
            self::CACHE_KEYS['ticket_priorities'],
            self::CACHE_TTL['ticket_priorities'],
            function () {
                return [
                    'low' => 'Низкий',
                    'medium' => 'Средний',
                    'high' => 'Высокий',
                    'urgent' => 'Срочный',
                ];
            }
        );
    }

    
     * Получить список статусов заявок

    public function getTicketStatuses()
    {
        return Cache::remember(
            self::CACHE_KEYS['ticket_statuses'],
            self::CACHE_TTL['ticket_statuses'],
            function () {
                return [
                    'open' => 'Открыта',
                    'in_progress' => 'В работе',
                    'resolved' => 'Решена',
                    'closed' => 'Закрыта',
                ];
            }
        );
    }

    
     * Очистить кеш для конкретного типа данных

    public function clearCache(string $type): void
    {
        if (isset(self::CACHE_KEYS[$type])) {
            Cache::forget(self::CACHE_KEYS[$type]);
            Log::info("Cache cleared for type: {$type}");
        }
    }

    
     * Очистить весь кеш приложения

    public function clearAllCache(): void
    {
        foreach (self::CACHE_KEYS as $key) {
            Cache::forget($key);
        }
        Log::info('All application cache cleared');
    }

    
     * Очистить кеш, связанный с пользователями

    public function clearUserRelatedCache(): void
    {
        $this->clearCache('assignable_users');
        Log::info('User-related cache cleared');
    }

    
     * Очистить кеш, связанный с заявками

    public function clearTicketRelatedCache(): void
    {
        $this->clearCache('ticket_categories');
        $this->clearCache('ticket_priorities');
        $this->clearCache('ticket_statuses');
        Log::info('Ticket-related cache cleared');
    }

    
     * Очистить кеш, связанный с оборудованием

    public function clearEquipmentRelatedCache(): void
    {
        $this->clearCache('equipment_categories');
        $this->clearCache('active_equipment');
        $this->clearCache('rooms');
        Log::info('Equipment-related cache cleared');
    }

    
     * Очистить кеш, связанный с местоположениями

    public function clearLocationRelatedCache(): void
    {
        $this->clearCache('locations');
        $this->clearCache('rooms');
        Log::info('Location-related cache cleared');
    }

    
     * Получить статистику кеша

    public function getCacheStats(): array
    {
        $stats = [];
        
        foreach (self::CACHE_KEYS as $type => $key) {
            $stats[$type] = [
                'key' => $key,
                'ttl' => self::CACHE_TTL[$type],
                'exists' => Cache::has($key),
            ];
        }

        return $stats;
    }

    
     * Предварительно загрузить все кеши

    public function warmUpCache(): void
    {
        try {
            $this->getRoles();
            $this->getLocations();
            $this->getActiveRooms();
            $this->getEquipmentCategories();
            $this->getActiveEquipment();
            $this->getAssignableUsers();
            $this->getTicketCategories();
            $this->getTicketPriorities();
            $this->getTicketStatuses();
            
            Log::info('Cache warmed up successfully');
        } catch (\Exception $e) {
            Log::error('Failed to warm up cache: ' . $e->getMessage());
        }
    }
}
