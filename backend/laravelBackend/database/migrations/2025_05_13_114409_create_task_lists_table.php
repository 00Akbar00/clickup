<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lists', function (Blueprint $table) {
            $table->uuid('list_id')->primary();

            $table->uuid('project_id');
            $table->uuid('created_by');
            
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('status')->default('active'); // active | archived
            $table->integer('position')->nullable(); // or not nullable, depending


            $table->timestamps();

            // Foreign Keys
            $table->foreign('project_id')
                  ->references('project_id')
                  ->on('projects')
                  ->onDelete('cascade');

            $table->foreign('created_by')
                  ->references('user_id')
                  ->on('users')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lists');
    }
};
