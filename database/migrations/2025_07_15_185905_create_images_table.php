<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('images', function (Blueprint $table) {
            $table->id();
            $table->morphs('imageable'); // Creates imageable_type and imageable_id columns
            $table->string('image_path'); // Storage path
            $table->string('alt_text')->nullable(); // Alt text for accessibility
            $table->string('mime_type')->nullable(); // MIME type
            $table->unsignedBigInteger('size')->nullable(); // File size in bytes
            $table->json('dimensions')->nullable(); // {"width": 1920, "height": 1080}
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Additional index for better performance (morphs already creates basic index)
            $table->index(['imageable_type', 'imageable_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('images');
    }
};
