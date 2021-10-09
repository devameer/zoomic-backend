<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePageItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('page_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->nullable()->constrained('pages')->nullOnDelete()->cascadeOnUpdate();
            $table->string('slug')->unique();
            $table->boolean('is_published')->default(1);
            $table->text('image')->nullable();
            $table->timestamps();
        });
        Schema::create('page_items_translations', function (Blueprint $table) {
            $table->foreignId('page_item_id')->nullable()->constrained('page_items')->nullOnDelete()->cascadeOnUpdate();
            $table->string('title');
            $table->string('description');
            $table->text('content');
            $table->text('additionals');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('page_items');
        Schema::dropIfExists('page_items_translations');
    }
}
