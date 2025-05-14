<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('workspace_members', function (Blueprint $table) {
            
            $table->uuid('workspace_member_id')->primary();
            $table->uuid('workspace_id');
            $table->uuid('user_id');
            $table->string('role'); // Possible values: owner | admin | member | guest
            $table->timestamp('joined_at')->useCurrent();
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('workspace_id')
                ->references('workspace_id')
                ->on('workspaces')
                ->onDelete('cascade');

            $table->foreign('user_id')
                ->references('user_id')
                ->on('users')
                ->onDelete('cascade');

            $table->unique(['workspace_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workspace_members');
    }
};
