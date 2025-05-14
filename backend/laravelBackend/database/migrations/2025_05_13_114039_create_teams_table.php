<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teams', function (Blueprint $table) {
            $table->uuid('team_id')->primary();
            
            $table->uuid('workspace_id');
            $table->uuid('created_by');
            
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('visibility', ['public', 'private'])->default('private');
            $table->string('color_code')->nullable();
            
            $table->timestamps();

            // Foreign Keys
            $table->foreign('workspace_id')
                ->references('workspace_id')
                ->on('workspaces')
                ->onDelete('cascade');

            $table->foreign('created_by')
                ->references('user_id')
                ->on('users')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teams');
    }
};
