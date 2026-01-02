<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('claim_id')->constrained('claims');
            $table->foreignId('contract_id')->constrained('contracts');   
            $table->foreignId('created_by')->constrained('users');
            $table->decimal('amount', 15, 2);
            $table->enum('status', ['paid', 'pending']);
            $table->string('type');
            $table->string('description');

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('payments');
    }
};