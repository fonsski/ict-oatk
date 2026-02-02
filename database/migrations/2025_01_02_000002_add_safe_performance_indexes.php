<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    
     * Check if index exists on table

    private function indexExists(string $table, string $index): bool
    {
        try {
            $indexes = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$index]);
            return !empty($indexes);
        } catch (\Exception $e) {
            return false;
        }
    }

    
     * Check if column exists on table

    private function columnExists(string $table, string $column): bool
    {
        try {
            $columns = DB::select("SHOW COLUMNS FROM {$table} LIKE '{$column}'");
            return !empty($columns);
        } catch (\Exception $e) {
            return false;
        }
    }

    
     * Run the migrations.

    public function up(): void
    {
        
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if ($this->columnExists('users', 'name') && !$this->indexExists('users', 'users_name_index')) {
                    $table->index('name');
                }
                
                if ($this->columnExists('users', 'is_active') && !$this->indexExists('users', 'users_is_active_index')) {
                    $table->index('is_active');
                }
                
                if ($this->columnExists('users', 'is_active') && !$this->indexExists('users', 'users_role_id_is_active_index')) {
                    $table->index(['role_id', 'is_active']);
                }
                
                if ($this->columnExists('users', 'last_login_at') && !$this->indexExists('users', 'users_last_login_at_index')) {
                    $table->index('last_login_at');
                }
            });
        }

        
        if (Schema::hasTable('tickets')) {
            Schema::table('tickets', function (Blueprint $table) {
                if ($this->columnExists('tickets', 'status') && !$this->indexExists('tickets', 'tickets_status_index')) {
                    $table->index('status');
                }
                
                if ($this->columnExists('tickets', 'priority') && !$this->indexExists('tickets', 'tickets_priority_index')) {
                    $table->index('priority');
                }
                
                if ($this->columnExists('tickets', 'category') && !$this->indexExists('tickets', 'tickets_category_index')) {
                    $table->index('category');
                }
                
                if ($this->columnExists('tickets', 'assigned_to_id') && !$this->indexExists('tickets', 'tickets_assigned_to_id_index')) {
                    $table->index('assigned_to_id');
                }
                
                if ($this->columnExists('tickets', 'location_id') && !$this->indexExists('tickets', 'tickets_location_id_index')) {
                    $table->index('location_id');
                }
                
                if ($this->columnExists('tickets', 'room_id') && !$this->indexExists('tickets', 'tickets_room_id_index')) {
                    $table->index('room_id');
                }
                
                if ($this->columnExists('tickets', 'equipment_id') && !$this->indexExists('tickets', 'tickets_equipment_id_index')) {
                    $table->index('equipment_id');
                }
                
                if ($this->columnExists('tickets', 'title') && !$this->indexExists('tickets', 'tickets_title_index')) {
                    $table->index('title');
                }
                
                if ($this->columnExists('tickets', 'reporter_name') && !$this->indexExists('tickets', 'tickets_reporter_name_index')) {
                    $table->index('reporter_name');
                }
                
                if ($this->columnExists('tickets', 'reporter_phone') && !$this->indexExists('tickets', 'tickets_reporter_phone_index')) {
                    $table->index('reporter_phone');
                }
                
                if ($this->columnExists('tickets', 'created_at') && !$this->indexExists('tickets', 'tickets_created_at_index')) {
                    $table->index('created_at');
                }
                
                if ($this->columnExists('tickets', 'updated_at') && !$this->indexExists('tickets', 'tickets_updated_at_index')) {
                    $table->index('updated_at');
                }
            });
        }

        
        if (Schema::hasTable('ticket_comments')) {
            Schema::table('ticket_comments', function (Blueprint $table) {
                if ($this->columnExists('ticket_comments', 'ticket_id') && !$this->indexExists('ticket_comments', 'ticket_comments_ticket_id_index')) {
                    $table->index('ticket_id');
                }
                
                if ($this->columnExists('ticket_comments', 'user_id') && !$this->indexExists('ticket_comments', 'ticket_comments_user_id_index')) {
                    $table->index('user_id');
                }
                
                if ($this->columnExists('ticket_comments', 'is_system') && !$this->indexExists('ticket_comments', 'ticket_comments_is_system_index')) {
                    $table->index('is_system');
                }
            });
        }

        
        if (Schema::hasTable('equipment')) {
            Schema::table('equipment', function (Blueprint $table) {
                if ($this->columnExists('equipment', 'name') && !$this->indexExists('equipment', 'equipment_name_index')) {
                    $table->index('name');
                }
                
                if ($this->columnExists('equipment', 'model') && !$this->indexExists('equipment', 'equipment_model_index')) {
                    $table->index('model');
                }
                
                if ($this->columnExists('equipment', 'serial_number') && !$this->indexExists('equipment', 'equipment_serial_number_index')) {
                    $table->index('serial_number');
                }
                
                if ($this->columnExists('equipment', 'category_id') && !$this->indexExists('equipment', 'equipment_category_id_index')) {
                    $table->index('category_id');
                }
                
                if ($this->columnExists('equipment', 'room_id') && !$this->indexExists('equipment', 'equipment_room_id_index')) {
                    $table->index('room_id');
                }
                
                if ($this->columnExists('equipment', 'status') && !$this->indexExists('equipment', 'equipment_status_index')) {
                    $table->index('status');
                }
            });
        }

        
        if (Schema::hasTable('rooms')) {
            Schema::table('rooms', function (Blueprint $table) {
                if ($this->columnExists('rooms', 'number') && !$this->indexExists('rooms', 'rooms_number_index')) {
                    $table->index('number');
                }
                
                if ($this->columnExists('rooms', 'name') && !$this->indexExists('rooms', 'rooms_name_index')) {
                    $table->index('name');
                }
                
                if ($this->columnExists('rooms', 'type') && !$this->indexExists('rooms', 'rooms_type_index')) {
                    $table->index('type');
                }
                
                if ($this->columnExists('rooms', 'building') && !$this->indexExists('rooms', 'rooms_building_index')) {
                    $table->index('building');
                }
                
                if ($this->columnExists('rooms', 'floor') && !$this->indexExists('rooms', 'rooms_floor_index')) {
                    $table->index('floor');
                }
                
                if ($this->columnExists('rooms', 'responsible_user_id') && !$this->indexExists('rooms', 'rooms_responsible_user_id_index')) {
                    $table->index('responsible_user_id');
                }
            });
        }

        
        if (Schema::hasTable('locations')) {
            Schema::table('locations', function (Blueprint $table) {
                if ($this->columnExists('locations', 'name') && !$this->indexExists('locations', 'locations_name_index')) {
                    $table->index('name');
                }
            });
        }

        
        if (Schema::hasTable('equipment_categories')) {
            Schema::table('equipment_categories', function (Blueprint $table) {
                if ($this->columnExists('equipment_categories', 'name') && !$this->indexExists('equipment_categories', 'equipment_categories_name_index')) {
                    $table->index('name');
                }
            });
        }

        
        if (Schema::hasTable('roles')) {
            Schema::table('roles', function (Blueprint $table) {
                if ($this->columnExists('roles', 'slug') && !$this->indexExists('roles', 'roles_slug_index')) {
                    $table->index('slug');
                }
            });
        }

        
        if (Schema::hasTable('notifications')) {
            Schema::table('notifications', function (Blueprint $table) {
                if ($this->columnExists('notifications', 'notifiable_id') && !$this->indexExists('notifications', 'notifications_notifiable_id_index')) {
                    $table->index('notifiable_id');
                }
                
                if ($this->columnExists('notifications', 'type') && !$this->indexExists('notifications', 'notifications_type_index')) {
                    $table->index('type');
                }
                
                if ($this->columnExists('notifications', 'read_at') && !$this->indexExists('notifications', 'notifications_read_at_index')) {
                    $table->index('read_at');
                }
            });
        }
    }

    
     * Reverse the migrations.

    public function down(): void
    {
        
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if ($this->indexExists('users', 'users_name_index')) {
                    $table->dropIndex(['name']);
                }
                if ($this->indexExists('users', 'users_is_active_index')) {
                    $table->dropIndex(['is_active']);
                }
                if ($this->indexExists('users', 'users_role_id_is_active_index')) {
                    $table->dropIndex(['role_id', 'is_active']);
                }
                if ($this->indexExists('users', 'users_last_login_at_index')) {
                    $table->dropIndex(['last_login_at']);
                }
            });
        }

        if (Schema::hasTable('tickets')) {
            Schema::table('tickets', function (Blueprint $table) {
                if ($this->indexExists('tickets', 'tickets_status_index')) {
                    $table->dropIndex(['status']);
                }
                if ($this->indexExists('tickets', 'tickets_priority_index')) {
                    $table->dropIndex(['priority']);
                }
                if ($this->indexExists('tickets', 'tickets_category_index')) {
                    $table->dropIndex(['category']);
                }
                if ($this->indexExists('tickets', 'tickets_assigned_to_id_index')) {
                    $table->dropIndex(['assigned_to_id']);
                }
                if ($this->indexExists('tickets', 'tickets_location_id_index')) {
                    $table->dropIndex(['location_id']);
                }
                if ($this->indexExists('tickets', 'tickets_room_id_index')) {
                    $table->dropIndex(['room_id']);
                }
                if ($this->indexExists('tickets', 'tickets_equipment_id_index')) {
                    $table->dropIndex(['equipment_id']);
                }
                if ($this->indexExists('tickets', 'tickets_title_index')) {
                    $table->dropIndex(['title']);
                }
                if ($this->indexExists('tickets', 'tickets_reporter_name_index')) {
                    $table->dropIndex(['reporter_name']);
                }
                if ($this->indexExists('tickets', 'tickets_reporter_phone_index')) {
                    $table->dropIndex(['reporter_phone']);
                }
                if ($this->indexExists('tickets', 'tickets_created_at_index')) {
                    $table->dropIndex(['created_at']);
                }
                if ($this->indexExists('tickets', 'tickets_updated_at_index')) {
                    $table->dropIndex(['updated_at']);
                }
            });
        }

        
        
    }
};

