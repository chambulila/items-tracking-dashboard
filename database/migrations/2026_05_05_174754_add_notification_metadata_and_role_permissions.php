<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('role_permissions')) {
            Schema::create('role_permissions', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('role_id')->constrained()->cascadeOnDelete();
                $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
                $table->timestamps();
                $table->unique(['role_id', 'permission_id']);
            });
        }

        Schema::table('notifications', function (Blueprint $table): void {
            if (! Schema::hasColumn('notifications', 'category')) {
                $table->string('category')->nullable()->after('type')->index();
            }

            if (! Schema::hasColumn('notifications', 'level')) {
                $table->string('level')->default('info')->after('category');
            }

            if (! Schema::hasColumn('notifications', 'entity_type')) {
                $table->string('entity_type')->nullable()->after('data');
            }

            if (! Schema::hasColumn('notifications', 'entity_id')) {
                $table->unsignedBigInteger('entity_id')->nullable()->after('entity_type');
            }

            if (! Schema::hasColumn('notifications', 'action_url')) {
                $table->string('action_url')->nullable()->after('entity_id');
            }

            if (! Schema::hasColumn('notifications', 'created_by')) {
                $table->foreignId('created_by')->nullable()->after('action_url')->constrained('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table): void {
            foreach (['created_by', 'action_url', 'entity_id', 'entity_type', 'level', 'category'] as $column) {
                if (! Schema::hasColumn('notifications', $column)) {
                    continue;
                }

                if ($column === 'created_by') {
                    $table->dropConstrainedForeignId($column);

                    continue;
                }

                $table->dropColumn($column);
            }
        });

        Schema::dropIfExists('role_permissions');
    }
};
