<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('team_members', function (Blueprint $table) {
            $table->uuid('team_member_id')->primary();
            $table->uuid('team_id');  
            $table->uuid('user_id');  

            $table->string('role'); // owner | member 
            $table->timestamp('joined_at')->useCurrent();
            $table->timestamps();

            // Constraints
            $table->unique(['team_id', 'user_id']);

            // Foreign Keys
            $table->foreign('team_id')
                ->references('team_id')  
                ->on('teams')
                ->onDelete('cascade');

            $table->foreign('user_id')
                ->references('user_id')  
                ->on('users')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_members');
    }
};
