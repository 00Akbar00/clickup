<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id('project_id');

            $table->unsignedBigInteger('team_id');
            $table->uuid('created_by');

            $table->string('name');
            $table->text('description')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('status')->default('active');
            $table->string('color_code')->nullable();
            $table->timestamps();

            // Foreign Keys
            $table->foreign('team_id')
                ->references('team_id')
                ->on('teams')
                ->onDelete('cascade');

            $table->foreign('created_by')
                ->references('user_id')
                ->on('users')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
