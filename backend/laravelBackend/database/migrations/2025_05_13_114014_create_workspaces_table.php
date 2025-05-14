<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('workspaces', function (Blueprint $table) {
            $table->id('workspace_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->uuid('created_by');
            $table->string('invite_token')->unique()->nullable();
            $table->timestamp('invite_token_expires_at')->nullable();
            $table->string('logo_url')->nullable();
            $table->timestamps();
            
            $table->foreign('created_by')->references('user_id')->on('users');
        });
    }

    public function down()
    {
        Schema::dropIfExists('workspaces');
    }
};
