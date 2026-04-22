<?php

namespace App\Traits;

trait QueryOptimizer
{
    /**
     * Scope для оптимизированной загрузки связанных данных пользователей
     */
    public function scopeWithUserData($query)
    {
        return $query->with([
            'user:id,name,phone,role_id,is_active',
            'user.role:id,name,slug'
        ]);
    }

    /**
     * Scope для оптимизированной загрузки связанных данных заявок
     */
    public function scopeWithTicketData($query)
    {
        return $query->with([
            'tickets:id,user_id,title,status,priority,category,created_at',
            'tickets.comments:id,ticket_id,user_id,content,created_at'
        ]);
    }

    /**
     * Scope для оптимизированной загрузки связанных данных оборудования
     */
    public function scopeWithEquipmentData($query)
    {
        return $query->with([
            'equipment:id,name,model,serial_number,category_id,room_id',
            'equipment.category:id,name',
            'equipment.room:id,number,name,type,building,floor'
        ]);
    }

    /**
     * Scope для оптимизированной загрузки связанных данных местоположений
     */
    public function scopeWithLocationData($query)
    {
        return $query->with([
            'location:id,name',
            'room:id,number,name,type,building,floor,location_id'
        ]);
    }

    /**
     * Scope для оптимизированной загрузки связанных данных назначений
     */
    public function scopeWithAssignmentData($query)
    {
        return $query->with([
            'assignedTo:id,name,phone,role_id',
            'assignedTo.role:id,name,slug'
        ]);
    }

    /**
     * Scope для оптимизированной загрузки всех связанных данных заявки
     */
    public function scopeWithFullTicketData($query)
    {
        return $query->with([
            'user:id,name,phone,role_id',
            'user.role:id,name,slug',
            'location:id,name',
            'assignedTo:id,name,phone,role_id',
            'assignedTo.role:id,name,slug',
            'room:id,number,name,type,building,floor',
            'equipment:id,name,model,serial_number',
            'comments.user:id,name',
            'comments' => function ($q) {
                $q->orderBy('created_at', 'desc');
            }
        ]);
    }

    /**
     * Scope для оптимизированной загрузки всех связанных данных пользователя
     */
    public function scopeWithFullUserData($query)
    {
        return $query->with([
            'role:id,name,slug',
            'tickets:id,user_id,title,status,priority,category,created_at',
            'tickets.comments:id,ticket_id,user_id,content,created_at',
            'assignedTickets:id,assigned_to_id,title,status,priority,category,created_at',
            'responsibleForRooms:id,responsible_user_id,number,name,type,building,floor'
        ]);
    }

    /**
     * Scope для загрузки только необходимых полей
     */
    public function scopeSelectEssential($query, array $additionalFields = [])
    {
        $essentialFields = array_merge([
            'id',
            'created_at',
            'updated_at'
        ], $additionalFields);

        return $query->select($essentialFields);
    }

    /**
     * Scope для ограничения количества связанных записей
     */
    public function scopeWithLimited($query, string $relation, int $limit = 10, string $orderBy = 'created_at')
    {
        return $query->with([
            $relation => function ($q) use ($limit, $orderBy) {
                $q->orderBy($orderBy, 'desc')->limit($limit);
            }
        ]);
    }
}