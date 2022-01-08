<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('phone')->unique();
            $table->string('email')->unique()->nullable();
            $table->string('password');
            $table->decimal('wallet', 5, 2)->default('0.00');
            $table->unsignedBigInteger('directrefer_id')->nullable();
            $table->unsignedBigInteger('indirectrefer_id')->nullable();
            $table->unsignedInteger('level_id')->default(1);
            $table->integer('recharge')->default(0);
            $table->integer('refers')->default(0);
            $table->string('referral_code',10)->unique();
            $table->boolean('active')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
}
