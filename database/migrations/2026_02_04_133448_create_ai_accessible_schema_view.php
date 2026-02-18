<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Create the `ai_accessible_schema` view exposing column metadata for the application's tables.
     *
     * The view contains table_name, column_name, data_type, is_nullable, and column_key for
     * the tables: workflows, tasks, customers, chat_threads, and chat_messages.
     * For SQLite, the view is built from `pragma_table_info`; for other drivers, it is built
     * from `information_schema.COLUMNS`.
     *
     * @return void
     */
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

    /**
     * Remove the ai_accessible_schema view from the database if it exists.
     */
    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS ai_accessible_schema;');
    }
};