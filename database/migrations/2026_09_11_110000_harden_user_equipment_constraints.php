<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'disabled_at')) {
            Schema::table('users', fn (Blueprint $table) => $table->timestamp('disabled_at')->nullable());
        }

        if (Schema::hasTable('equipment') && DB::getDriverName() === 'mysql') {
            $constraints = DB::table('information_schema.TABLE_CONSTRAINTS')
                ->where('TABLE_SCHEMA', DB::getDatabaseName())
                ->where('TABLE_NAME', 'equipment')
                ->pluck('CONSTRAINT_NAME')
                ->all();
            foreach ([
                'equipment_total_quantity_check' => 'total_quantity >= 1',
                'equipment_available_quantity_check' => 'available_quantity >= 0',
                'equipment_available_within_total_check' => 'available_quantity <= total_quantity',
            ] as $name => $expression) {
                if (! in_array($name, $constraints, true)) {
                    DB::statement("alter table `equipment` add constraint `$name` check ($expression)");
                }
            }
        }

    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'disabled_at')) {
            Schema::table('users', fn (Blueprint $table) => $table->dropColumn('disabled_at'));
        }
    }
};
