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
        Schema::table('learning_resources', function (Blueprint $table) {
            $table->foreignId('grade_level_id')
                ->nullable()
                ->after('learning_resource_type_id')
                ->constrained()
                ->nullOnDelete();
            $table->string('author')->nullable()->after('title');
            $table->string('language')->nullable()->after('publisher');
            $table->string('subject')->nullable()->after('language');
            $table->string('volume')->nullable()->after('subject');
            $table->string('edition')->nullable()->after('volume');
            $table->unsignedSmallInteger('copyright_year')->nullable()->after('edition');
            $table->unsignedInteger('pages')->nullable()->after('copyright_year');
            $table->string('isbn')->nullable()->after('pages');
            $table->string('attachment_path')->nullable()->after('isbn');
            $table->string('cover_image_path')->nullable()->after('attachment_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('learning_resources', function (Blueprint $table) {
            $table->dropConstrainedForeignId('grade_level_id');
            $table->dropColumn([
                'author',
                'language',
                'subject',
                'volume',
                'edition',
                'copyright_year',
                'pages',
                'isbn',
                'attachment_path',
                'cover_image_path',
            ]);
        });
    }
};
