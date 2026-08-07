<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Метка гостя, подавшего заявку без входа в систему.
     *
     * Такая же строка кладётся посетителю в долгоживущую cookie. По ней он
     * потом видит свои обращения и может их поправить — раньше заявка
     * пропадала из виду сразу после отправки, и вместо исправления опечатки
     * человек заводил вторую.
     *
     * Значение — секрет: кто предъявил метку, тот и видит заявки, поэтому
     * она длинная, случайная и наружу нигде не показывается.
     */
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->string('guest_token', 64)->nullable()->after('user_id');

            // Выборка «мои заявки» идёт ровно по этому полю.
            $table->index('guest_token');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropIndex(['guest_token']);
            $table->dropColumn('guest_token');
        });
    }
};
