-- ============================================================
-- 003_align_users_with_laravel.sql
-- MySQL 8.4 / City Bikes
--
-- Zosúladenie tabuľky users s Laravelom / Breeze.
--
-- Predpoklad:
--   Skripty 001 a 002 už boli úspešne vykonané.
--
-- Zmeny:
--   1. Premenovanie password_hash na password.
--   2. Pridanie remember_token pre zapamätanie prihlásenia.
--
-- Existujúce účty a hashe hesiel zostanú zachované.
-- Skript je určený na jednorazové spustenie.
-- ============================================================

USE city_bikes;


ALTER TABLE users

    -- Mení sa iba názov stĺpca.
    -- Typ VARCHAR(255), povinnosť vyplnenia aj uložené
   
    
    -- Heslá naďalej hashuje aplikácia Laravel.
    RENAME COLUMN password_hash TO password,

    -- Token spravuje Laravel
    ADD COLUMN remember_token VARCHAR(100) NULL;


-- Overenie štruktúry upravených stĺpcov.
SHOW COLUMNS FROM users
WHERE Field IN ('password', 'remember_token');