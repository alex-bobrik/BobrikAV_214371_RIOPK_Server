<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['company_id']);

            $table->unsignedBigInteger('company_id')->nullable()->change();

            $table->foreign('company_id')
                ->references('id')
                ->on('companies');
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['company_id']);

            // 2. Возвращаем NOT NULL (но только если в БД нет NULL-значений!)
            $table->unsignedBigInteger('company_id')->nullable(false)->change();

            // 3. Добавляем обратно strict foreign key
            $table->foreign('company_id')
                ->references('id')
                ->on('companies');
        });
    }
};
