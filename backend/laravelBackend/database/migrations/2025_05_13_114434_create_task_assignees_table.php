<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('task_assignees', function (Blueprint $table) {
            $table->uuid('task_assignee_id')->primary();
            $table->uuid('task_id');
            $table->uuid('team_member_id'); 
            $table->uuid('assigned_by'); 
            $table->timestamp('assigned_at')->useCurrent();
            $table->timestamps();

            // Foreign keys
            $table->foreign('task_id')->references('task_id')->on('tasks')->onDelete('cascade');
            $table->foreign('team_member_id')->references('team_member_id')->on('team_members')->onDelete('cascade');
            $table->foreign('assigned_by')->references('user_id')->on('users')->onDelete('cascade');

            $table->unique(['task_id', 'team_member_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('task_assignees');
    }
};
