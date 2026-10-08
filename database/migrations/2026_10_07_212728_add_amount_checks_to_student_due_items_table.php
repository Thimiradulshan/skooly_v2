<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            DB::unprepared(<<<'SQL'
                CREATE TRIGGER student_due_items_amounts_check_insert
                BEFORE INSERT ON student_due_items
                FOR EACH ROW WHEN NEW.original_amount < 0
                    OR NEW.discount_amount < 0
                    OR NEW.net_amount < 0
                    OR NEW.paid_amount < 0
                    OR NEW.balance_amount < 0
                    OR NEW.paid_amount + NEW.balance_amount != NEW.net_amount
                BEGIN
                    SELECT RAISE(ABORT, 'student due item amount constraint failed');
                END
            SQL);
            DB::unprepared(<<<'SQL'
                CREATE TRIGGER student_due_items_amounts_check_update
                BEFORE UPDATE ON student_due_items
                FOR EACH ROW WHEN NEW.original_amount < 0
                    OR NEW.discount_amount < 0
                    OR NEW.net_amount < 0
                    OR NEW.paid_amount < 0
                    OR NEW.balance_amount < 0
                    OR NEW.paid_amount + NEW.balance_amount != NEW.net_amount
                BEGIN
                    SELECT RAISE(ABORT, 'student due item amount constraint failed');
                END
            SQL);

            return;
        }

        DB::statement(<<<'SQL'
            ALTER TABLE student_due_items
                ADD CONSTRAINT student_due_items_amounts_check
                CHECK (
                    original_amount >= 0
                    AND discount_amount >= 0
                    AND net_amount >= 0
                    AND paid_amount >= 0
                    AND balance_amount >= 0
                    AND paid_amount + balance_amount = net_amount
                )
        SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            DB::unprepared('DROP TRIGGER IF EXISTS student_due_items_amounts_check_insert');
            DB::unprepared('DROP TRIGGER IF EXISTS student_due_items_amounts_check_update');

            return;
        }

        DB::statement('ALTER TABLE student_due_items DROP CHECK student_due_items_amounts_check');
    }
};
