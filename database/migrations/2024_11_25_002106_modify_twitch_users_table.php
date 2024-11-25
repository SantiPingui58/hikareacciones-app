<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('twitch_users', function (Blueprint $table) {
            $table->string('access_token')->nullable();
            $table->string('refresh_token')->nullable();   
            $table->timestamp('token_expiration')->nullable();  
        });
    }
    
    public function down()
    {
        Schema::table('twitch_users', function (Blueprint $table) {
            $table->dropColumn(['access_token', 'token_expiration', 'refresh_token']);
        });
    }
    
};
