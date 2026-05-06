<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('devices', function (Blueprint $table): void {
            if (! Schema::hasColumn('devices', 'device_uuid')) {
                $table->string('device_uuid')->nullable()->unique()->after('user_id');
            }

            if (! Schema::hasColumn('devices', 'device_type')) {
                $table->string('device_type')->nullable()->after('name');
            }

            if (! Schema::hasColumn('devices', 'lost_item_id')) {
                $table->foreignId('lost_item_id')->nullable()->after('user_id')->constrained('lost_items')->nullOnDelete();
            }

            if (! Schema::hasColumn('devices', 'brand')) {
                $table->string('brand')->nullable()->after('brand_model');
            }

            if (! Schema::hasColumn('devices', 'model')) {
                $table->string('model')->nullable()->after('brand');
            }

            if (! Schema::hasColumn('devices', 'os_name')) {
                $table->string('os_name')->nullable()->after('model');
            }

            if (! Schema::hasColumn('devices', 'os_version')) {
                $table->string('os_version')->nullable()->after('os_name');
            }

            if (! Schema::hasColumn('devices', 'app_version')) {
                $table->string('app_version')->nullable()->after('os_version');
            }

            if (! Schema::hasColumn('devices', 'manual_imei')) {
                $table->string('manual_imei')->nullable()->after('serial_imei');
            }

            if (! Schema::hasColumn('devices', 'serial_number')) {
                $table->string('serial_number')->nullable()->after('manual_imei');
            }

            if (! Schema::hasColumn('devices', 'fcm_token')) {
                $table->text('fcm_token')->nullable()->after('serial_number');
            }

            if (! Schema::hasColumn('devices', 'location_permission_status')) {
                $table->string('location_permission_status')->default('unknown')->after('fcm_token');
            }

            if (! Schema::hasColumn('devices', 'tracking_mode')) {
                $table->string('tracking_mode')->default('idle')->after('is_lost');
            }

            if (! Schema::hasColumn('devices', 'latitude')) {
                $table->decimal('latitude', 10, 7)->nullable()->after('is_lost');
            }

            if (! Schema::hasColumn('devices', 'longitude')) {
                $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            }

            if (! Schema::hasColumn('devices', 'last_seen_at')) {
                $table->timestamp('last_seen_at')->nullable()->after('longitude');
            }

            if (! Schema::hasColumn('devices', 'last_latitude')) {
                $table->decimal('last_latitude', 10, 7)->nullable()->after('last_seen_at');
            }

            if (! Schema::hasColumn('devices', 'last_longitude')) {
                $table->decimal('last_longitude', 10, 7)->nullable()->after('last_latitude');
            }

            if (! Schema::hasColumn('devices', 'last_accuracy')) {
                $table->decimal('last_accuracy', 8, 2)->nullable()->after('last_longitude');
            }

            if (! Schema::hasColumn('devices', 'last_battery_level')) {
                $table->unsignedTinyInteger('last_battery_level')->nullable()->after('last_accuracy');
            }

            if (! Schema::hasColumn('devices', 'active_search_started_at')) {
                $table->timestamp('active_search_started_at')->nullable()->after('last_battery_level');
            }

            if (! Schema::hasColumn('devices', 'active_search_ended_at')) {
                $table->timestamp('active_search_ended_at')->nullable()->after('active_search_started_at');
            }
        });

        Schema::table('device_locations', function (Blueprint $table): void {
            if (! Schema::hasColumn('device_locations', 'speed')) {
                $table->decimal('speed', 8, 2)->nullable()->after('accuracy');
            }

            if (! Schema::hasColumn('device_locations', 'battery_level')) {
                $table->unsignedTinyInteger('battery_level')->nullable()->after('speed');
            }

            if (! Schema::hasColumn('device_locations', 'tracking_mode')) {
                $table->string('tracking_mode')->default('heartbeat')->after('battery_level');
            }

            if (! Schema::hasColumn('device_locations', 'is_inside_campus')) {
                $table->boolean('is_inside_campus')->nullable()->after('tracking_mode');
            }
        });
    }

    public function down(): void
    {
        Schema::table('device_locations', function (Blueprint $table): void {
            foreach (['is_inside_campus', 'tracking_mode', 'battery_level', 'speed'] as $column) {
                if (Schema::hasColumn('device_locations', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('devices', function (Blueprint $table): void {
            foreach ([
                'active_search_ended_at',
                'active_search_started_at',
                'last_battery_level',
                'last_accuracy',
                'last_longitude',
                'last_latitude',
                'last_seen_at',
                'longitude',
                'latitude',
                'tracking_mode',
                'location_permission_status',
                'fcm_token',
                'serial_number',
                'manual_imei',
                'app_version',
                'os_version',
                'os_name',
                'model',
                'brand',
                'device_type',
                'device_uuid',
            ] as $column) {
                if (Schema::hasColumn('devices', $column)) {
                    $table->dropColumn($column);
                }
            }

            if (Schema::hasColumn('devices', 'lost_item_id')) {
                $table->dropConstrainedForeignId('lost_item_id');
            }
        });
    }
};
