<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            $queries = [];
            $tables = ['workflows', 'tasks', 'customers', 'chat_threads', 'chat_messages'];
            foreach ($tables as $table) {
                $queries[] = "SELECT '$table' as table_name, name as column_name, type as data_type, 
                             case when \"notnull\" = 1 then 'NO' else 'YES' end as is_nullable,
                             case when pk = 1 then 'PRI' else '' end as column_key
                             FROM pragma_table_info('$table')";
            }
            $finalQuery = implode(' UNION ALL ', $queries);
            DB::statement("CREATE VIEW ai_accessible_schema AS $finalQuery");

            return;
        }

        DB::statement("
            CREATE VIEW ai_accessible_schema AS
            SELECT 
                c.TABLE_NAME as table_name,
                c.COLUMN_NAME as column_name,
                c.DATA_TYPE as data_type,
                c.IS_NULLABLE as is_nullable,
                c.COLUMN_KEY as column_key
            FROM information_schema.COLUMNS c
            WHERE c.TABLE_SCHEMA = DATABASE()
            AND c.TABLE_NAME IN ('workflows', 'tasks', 'customers', 'chat_threads', 'chat_messages')
            ORDER BY c.TABLE_NAME, c.ORDINAL_POSITION;
        ");
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS ai_accessible_schema;');
    }
};
