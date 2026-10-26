-- ============================================================
-- Inštalácia databázy City Bikes / MySQL 8.4
--
-- Spúšťaj cez klienta mysql z koreňového priečinka projektu:
-- SOURCE database/install.sql;
--
-- Určené na prvú inštaláciu do prázdnej databázy.
-- Pri chybe nepokračuj ďalším skriptom.
-- ============================================================

-- 1. Vytvorenie databázy a jej výber.
SOURCE database/changes/001_create_database.sql;

-- 2. Vytvorenie tabuliek, indexov, väzieb a základných rolí.
SOURCE database/changes/002_create_tables.sql;