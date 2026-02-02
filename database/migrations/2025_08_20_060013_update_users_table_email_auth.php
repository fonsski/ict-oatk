<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    
     * Run the migrations.

    public function up(): void
    {
        
        Schema::table("users", function (Blueprint $table) {
            $table->string("phone")->nullable()->change();
        });

        
        $users = DB::table("users")
            ->whereNull("email")
            ->whereNotNull("phone")
            ->get();

        foreach ($users as $user) {
            
            $tempEmail =
                "user_" .
                preg_replace("/[^0-9]/", "", $user->phone) .
                "@temp.domain";

            DB::table("users")
                ->where("id", $user->id)
                ->update(["email" => $tempEmail]);
        }

        
        Schema::table("users", function (Blueprint $table) {
            if (!Schema::hasIndex("users", "users_email_index")) {
                $table->index("email", "users_email_index");
            }
        });
    }

    
     * Reverse the migrations.

    public function down(): void
    {
        
        Schema::table("users", function (Blueprint $table) {
            if (Schema::hasIndex("users", "users_email_index")) {
                $table->dropIndex("users_email_index");
            }
        });

        
        Schema::table("users", function (Blueprint $table) {
            $table->string("phone")->nullable(false)->change();
        });
    }
};
