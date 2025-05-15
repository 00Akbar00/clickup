<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->uuid('task_id')->primary();

            $table->uuid('list_id');
            $table->uuid('created_by');
            
            $table->string('title');
            $table->text('description')->nullable();
            $table->timestamp('due_date')->nullable();

            $table->enum('priority', ['high', 'normal','low','clear'])->default('clear');
            $table->enum('status', ['todo', 'inprogress','completed'])->default('todo');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            // Foreign Keys
            $table->foreign('list_id')
                  ->references('list_id')
                  ->on('lists')
                  ->onDelete('cascade');

            $table->foreign('created_by')
                  ->references('user_id')
                  ->on('users')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
