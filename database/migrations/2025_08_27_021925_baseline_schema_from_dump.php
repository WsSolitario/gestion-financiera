<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Extensión para gen_random_uuid()
        DB::unprepared('CREATE EXTENSION IF NOT EXISTS "pgcrypto";');

        // ===== ENUMS =====
        DB::unprepared("
            DO $$ BEGIN
                IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'group_role') THEN
                    CREATE TYPE public.group_role AS ENUM ('owner','admin','member');
                END IF;
                IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'expense_status') THEN
                    CREATE TYPE public.expense_status AS ENUM ('pending','approved','rejected');
                END IF;
                IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'ocr_processing_status') THEN
                    CREATE TYPE public.ocr_processing_status AS ENUM ('pending','completed','failed','skipped');
                END IF;
                IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'invitation_status') THEN
                    CREATE TYPE public.invitation_status AS ENUM ('pending','accepted','expired');
                END IF;
                IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'device_platform') THEN
                    CREATE TYPE public.device_platform AS ENUM ('android','ios','web');
                END IF;
                IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'payment_status') THEN
                    CREATE TYPE public.payment_status AS ENUM ('pending','completed','failed');
                END IF;
            END $$;
        ");

        // ===== Helper trigger function =====
        DB::unprepared("
            CREATE OR REPLACE FUNCTION public.trigger_set_timestamp() RETURNS trigger
            LANGUAGE plpgsql AS $$
            BEGIN
              NEW.updated_at = NOW();
              RETURN NEW;
            END;
            $$;
        ");

        // ===== Tablas =====
        DB::unprepared("
            CREATE TABLE IF NOT EXISTS public.users (
                id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
                name varchar(100) NOT NULL,
                email varchar(255) NOT NULL UNIQUE,
                password_hash text NOT NULL,
                profile_picture_url text,
                phone_number varchar(50),
                created_at timestamptz DEFAULT CURRENT_TIMESTAMP,
                updated_at timestamptz DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS public.groups (
                id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
                name varchar(150) NOT NULL,
                description text,
                owner_id uuid,
                created_at timestamptz DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT groups_owner_id_fkey
                    FOREIGN KEY (owner_id) REFERENCES public.users(id) ON DELETE SET NULL
            );

            CREATE TABLE IF NOT EXISTS public.group_members (
                id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
                user_id uuid NOT NULL,
                group_id uuid,
                role public.group_role NOT NULL DEFAULT 'member',
                joined_at timestamptz DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT group_members_user_id_group_id_key UNIQUE (user_id, group_id),
                CONSTRAINT group_members_user_id_fkey
                    FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE,
                CONSTRAINT group_members_group_id_fkey
                    FOREIGN KEY (group_id) REFERENCES public.groups(id) ON DELETE CASCADE
            );
            -- Índice extra como en el dump (orden inverso)
            CREATE UNIQUE INDEX IF NOT EXISTS group_members_unique_user_group
                ON public.group_members (group_id, user_id);

            CREATE TABLE IF NOT EXISTS public.payments (
                id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
                from_user_id uuid,
                to_user_id uuid,
                group_id uuid,
                amount numeric(10,2) NOT NULL,
                unapplied_amount numeric(10,2) DEFAULT 0,
                payment_method varchar(100),
                note text,
                proof_url text,
                evidence_url text,
                signature text,
                status public.payment_status NOT NULL DEFAULT 'pending',
                payment_date timestamptz,
                created_at timestamptz DEFAULT CURRENT_TIMESTAMP,
                updated_at timestamptz DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT payments_from_user_id_fkey
                    FOREIGN KEY (from_user_id) REFERENCES public.users(id) ON DELETE SET NULL,
                CONSTRAINT payments_to_user_id_fkey
                    FOREIGN KEY (to_user_id) REFERENCES public.users(id) ON DELETE SET NULL,
                CONSTRAINT payments_group_id_fkey
                    FOREIGN KEY (group_id) REFERENCES public.groups(id) ON DELETE CASCADE
            );
            CREATE INDEX IF NOT EXISTS idx_payments_from ON public.payments(from_user_id);
            CREATE INDEX IF NOT EXISTS idx_payments_to   ON public.payments(to_user_id);

            CREATE TABLE IF NOT EXISTS public.expenses (
                id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
                description text NOT NULL,
                total_amount numeric(10,2) NOT NULL,
                payer_id uuid,
                group_id uuid,
                ticket_image_url text,
                ocr_status public.ocr_processing_status NOT NULL DEFAULT 'pending',
                ocr_raw_text text,
                status public.expense_status NOT NULL DEFAULT 'pending',
                expense_date date NOT NULL,
                created_at timestamptz DEFAULT CURRENT_TIMESTAMP,
                updated_at timestamptz DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT expenses_payer_id_fkey
                    FOREIGN KEY (payer_id) REFERENCES public.users(id) ON DELETE SET NULL,
                CONSTRAINT expenses_group_id_fkey
                    FOREIGN KEY (group_id) REFERENCES public.groups(id) ON DELETE CASCADE
            );

            CREATE TABLE IF NOT EXISTS public.expense_participants (
                id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
                expense_id uuid,
                user_id uuid,
                amount_due numeric(10,2) NOT NULL,
                is_paid boolean NOT NULL DEFAULT false,
                payment_id uuid,
                created_at timestamptz DEFAULT CURRENT_TIMESTAMP,
                updated_at timestamptz DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT expense_participants_expense_id_user_id_key UNIQUE (expense_id, user_id),
                CONSTRAINT expense_participants_expense_id_fkey
                    FOREIGN KEY (expense_id) REFERENCES public.expenses(id) ON DELETE CASCADE,
                CONSTRAINT expense_participants_user_id_fkey
                    FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE,
                CONSTRAINT expense_participants_payment_id_fkey
                    FOREIGN KEY (payment_id) REFERENCES public.payments(id) ON DELETE SET NULL
            );
            CREATE INDEX IF NOT EXISTS idx_ep_payment ON public.expense_participants(payment_id);

            CREATE TABLE IF NOT EXISTS public.invitations (
                id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
                inviter_id uuid,
                invitee_email varchar(255) NOT NULL,
                group_id uuid,
                token text NOT NULL,
                status public.invitation_status NOT NULL DEFAULT 'pending',
                expires_at timestamptz NOT NULL,
                created_at timestamptz(0) DEFAULT CURRENT_TIMESTAMP,
                updated_at timestamptz(0) DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT invitations_inviter_id_fkey
                    FOREIGN KEY (inviter_id) REFERENCES public.users(id) ON DELETE CASCADE,
                CONSTRAINT invitations_group_id_fkey
                    FOREIGN KEY (group_id) REFERENCES public.groups(id) ON DELETE CASCADE,
                CONSTRAINT invitations_token_key UNIQUE (token)
            );
            -- Índice parcial (único) como en el dump
            DO $$
            BEGIN
                IF NOT EXISTS (
                    SELECT 1 FROM pg_indexes
                    WHERE schemaname='public'
                      AND indexname='invitations_unique_pending_email_group'
                ) THEN
                    EXECUTE '
                        CREATE UNIQUE INDEX invitations_unique_pending_email_group
                        ON public.invitations (group_id, lower(invitee_email))
                        WHERE status = ''pending'';
                    ';
                END IF;
            END $$;

            -- Tabla de tokens personales (idéntica a tu dump)
            CREATE TABLE IF NOT EXISTS public.personal_access_tokens (
                id bigserial PRIMARY KEY,
                tokenable_type varchar(255) NOT NULL,
                tokenable_id uuid NOT NULL,
                name text NOT NULL,
                token varchar(64) NOT NULL UNIQUE,
                abilities text,
                last_used_at timestamp(0) without time zone,
                expires_at   timestamp(0) without time zone,
                created_at   timestamp(0) without time zone,
                updated_at   timestamp(0) without time zone
            );
            CREATE INDEX IF NOT EXISTS personal_access_tokens_tokenable_type_tokenable_id_index
                ON public.personal_access_tokens(tokenable_type, tokenable_id);
            CREATE INDEX IF NOT EXISTS personal_access_tokens_expires_at_index
                ON public.personal_access_tokens(expires_at);

            CREATE TABLE IF NOT EXISTS public.user_devices (
                id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
                user_id uuid,
                device_token text NOT NULL,
                device_type public.device_platform NOT NULL,
                created_at timestamptz DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT user_devices_user_id_fkey
                    FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE
            );
        ");

        // ===== Triggers updated_at =====
        DB::unprepared("
            DO $$
            BEGIN
                IF NOT EXISTS (
                    SELECT 1 FROM pg_trigger WHERE tgname = 'set_timestamp' AND tgrelid = 'public.users'::regclass
                ) THEN
                    CREATE TRIGGER set_timestamp
                    BEFORE UPDATE ON public.users
                    FOR EACH ROW EXECUTE FUNCTION public.trigger_set_timestamp();
                END IF;

                IF NOT EXISTS (
                    SELECT 1 FROM pg_trigger WHERE tgname = 'set_timestamp' AND tgrelid = 'public.expenses'::regclass
                ) THEN
                    CREATE TRIGGER set_timestamp
                    BEFORE UPDATE ON public.expenses
                    FOR EACH ROW EXECUTE FUNCTION public.trigger_set_timestamp();
                END IF;

                IF NOT EXISTS (
                    SELECT 1 FROM pg_trigger WHERE tgname = 'set_timestamp' AND tgrelid = 'public.expense_participants'::regclass
                ) THEN
                    CREATE TRIGGER set_timestamp
                    BEFORE UPDATE ON public.expense_participants
                    FOR EACH ROW EXECUTE FUNCTION public.trigger_set_timestamp();
                END IF;

                IF NOT EXISTS (
                    SELECT 1 FROM pg_trigger WHERE tgname = 'payments_set_timestamp'
                ) THEN
                    CREATE TRIGGER payments_set_timestamp
                    BEFORE UPDATE ON public.payments
                    FOR EACH ROW EXECUTE FUNCTION public.trigger_set_timestamp();
                END IF;
            END $$;
        ");
    }

    public function down(): void
    {
        // Quita triggers
        DB::unprepared("
            DROP TRIGGER IF EXISTS payments_set_timestamp ON public.payments;
            DROP TRIGGER IF EXISTS set_timestamp ON public.expenses;
            DROP TRIGGER IF EXISTS set_timestamp ON public.expense_participants;
            DROP TRIGGER IF EXISTS set_timestamp ON public.users;
        ");

        // Borra tablas (en orden por FKs)
        DB::unprepared("
            DROP TABLE IF EXISTS public.expense_participants CASCADE;
            DROP TABLE IF EXISTS public.invitations CASCADE;
            DROP TABLE IF EXISTS public.expenses CASCADE;
            DROP TABLE IF EXISTS public.payments CASCADE;
            DROP TABLE IF EXISTS public.group_members CASCADE;
            DROP TABLE IF EXISTS public.groups CASCADE;
            DROP TABLE IF EXISTS public.user_devices CASCADE;
            DROP TABLE IF EXISTS public.personal_access_tokens CASCADE;
            DROP TABLE IF EXISTS public.users CASCADE;
        ");

        // Borra función y enums
        DB::unprepared("DROP FUNCTION IF EXISTS public.trigger_set_timestamp();");
        DB::unprepared("
            DO $$ BEGIN
                IF EXISTS (SELECT 1 FROM pg_type WHERE typname='device_platform') THEN DROP TYPE public.device_platform; END IF;
                IF EXISTS (SELECT 1 FROM pg_type WHERE typname='payment_status') THEN DROP TYPE public.payment_status; END IF;
                IF EXISTS (SELECT 1 FROM pg_type WHERE typname='invitation_status') THEN DROP TYPE public.invitation_status; END IF;
                IF EXISTS (SELECT 1 FROM pg_type WHERE typname='ocr_processing_status') THEN DROP TYPE public.ocr_processing_status; END IF;
                IF EXISTS (SELECT 1 FROM pg_type WHERE typname='expense_status') THEN DROP TYPE public.expense_status; END IF;
                IF EXISTS (SELECT 1 FROM pg_type WHERE typname='group_role') THEN DROP TYPE public.group_role; END IF;
            END $$;
        ");
    }
};
