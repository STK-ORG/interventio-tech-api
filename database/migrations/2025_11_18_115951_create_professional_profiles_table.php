<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('professional_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->unique()
                ->constrained('users')
                ->onDelete('cascade');
            $table->string('company_name');
            $table->string('cfe_number', 100);
            $table->timestamps();

            $table->index('cfe_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('professional_profiles');
    }
};
