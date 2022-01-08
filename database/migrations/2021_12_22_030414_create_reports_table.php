<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReportsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->string('number',25)->nullable();
            $table->string('phone',11);
            $table->unsignedInteger('operator_id');
            $table->unsignedInteger('api_id');
            $table->string('txnid',20);
            $table->unsignedBigInteger('user_id');
            $table->decimal('total_amount', 5, 2);
            $table->decimal('paid_amount', 5, 2);
            $table->decimal('instant_commission', 3, 2);
            $table->decimal('wallet_used', 5, 2);
            $table->string('apitxnid',50)->nullable();
            $table->enum('status', ['pending','success','failed'])->default('pending');
            $table->enum('product', ['mobile','dth']);
            $table->string('lat_lon',25)->nullable();
            $table->enum('via', ['app','portal','api'])->default('app');
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
        Schema::dropIfExists('reports');
    }
}
