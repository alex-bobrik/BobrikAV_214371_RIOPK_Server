<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('insurer_id')->constrained('companies');
            $table->integer('coverage');
            $table->foreignId('reinsurer_id')->constrained('companies');
            $table->enum('status', ['active', 'denied', 'need_details', 'draft']);
            $table->string('terms');
            $table->string('number');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('contracts');
    }
};