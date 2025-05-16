<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('workspace_invitations', function (Blueprint $table) {
            $table->uuid('invitation_id')->primary();
            $table->uuid('workspace_id');
            $table->string('email');
            $table->string('role')->default('member');
            $table->string('inviter_name');
            $table->string('invite_token');
            $table->timestamp('expires_at');
            $table->enum('status', ['pending', 'accepted', 'expired'])->default('pending');
            $table->timestamps();

            $table->foreign('workspace_id')
                ->references('workspace_id')
                ->on('workspaces')
                ->onDelete('cascade');

            $table->unique(['workspace_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workspace_invitations');
    }
};
