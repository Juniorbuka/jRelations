<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resource_relation_type_templates', function (Blueprint $table): void {
            $table->unsignedInteger('relation_type_id');
            $table->unsignedInteger('template_id');
            $table->primary(['relation_type_id', 'template_id'], 'rr_type_templates_pk');
            $table->foreign('relation_type_id', 'rr_type_templates_type_fk')
                ->references('id')
                ->on('resource_relation_types')
                ->cascadeOnDelete();
            $table->foreign('template_id', 'rr_type_templates_template_fk')
                ->references('id')
                ->on('site_templates')
                ->cascadeOnDelete();
            $table->index('template_id', 'rr_type_templates_template_idx');
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('resource_relation_type_templates');
    }
};
