<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tokens', function (Blueprint $table) {
            $table->id();
            $table->string('code', 5)->unique();
            $table->boolean('is_used')->default(false);
            $table->boolean('is_custom')->default(false);
            $table->string('note')->nullable();
            $table->timestamp('valid_until')->nullable();
            $table->timestamp('used_at')->nullable();
            $table->timestamps();

            $table->index(['code', 'is_used']);
            $table->index('valid_until');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tokens');
    }
};