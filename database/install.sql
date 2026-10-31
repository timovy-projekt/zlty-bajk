-- Prvá inštalácia databázy.
-- Spúšťať cez klienta mysql z koreňového priečinka projektu.

SOURCE database/changes/001_create_database.sql;
SOURCE database/changes/002_create_tables.sql;
SOURCE database/changes/003_align_users_with_laravel.sql;