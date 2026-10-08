<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resource_relation_types', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('slug', 100)->unique();
            $table->string('name_uk', 150);
            $table->string('name_en', 150)->nullable();
            $table->timestamps();
        });

        Schema::create('resource_relation_links', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('relation_type_id');
            $table->unsignedInteger('resource_a_id');
            $table->unsignedInteger('resource_b_id');
            $table->foreign('relation_type_id', 'rr_links_type_fk')
                ->references('id')
                ->on('resource_relation_types')
                ->cascadeOnDelete();
            $table->foreign('resource_a_id', 'rr_links_a_resource_fk')
                ->references('id')
                ->on('site_content')
                ->cascadeOnDelete();
            $table->foreign('resource_b_id', 'rr_links_b_resource_fk')
                ->references('id')
                ->on('site_content')
                ->cascadeOnDelete();
            $table->unique(
                ['relation_type_id', 'resource_a_id', 'resource_b_id'],
                'rr_links_unique'
            );
            $table->index(['resource_a_id', 'relation_type_id'], 'rr_links_a_type_idx');
            $table->index(['resource_b_id', 'relation_type_id'], 'rr_links_b_type_idx');
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('resource_relation_links');
        Schema::dropIfExists('resource_relation_types');
    }
};
