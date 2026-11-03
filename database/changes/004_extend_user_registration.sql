-- ============================================================
-- 004_extend_user_registration.sql
-- City Bikes / MySQL 8.4
--
-- Predpoklady:
--   Skripty 001, 002 a 003 už boli úspešne vykonané.
--   Objekty pridávané týmto skriptom ešte neexistujú.
--
-- Zmeny:
--   1. Dátum narodenia používateľa.
--   2. Nepovinné číslo občianskeho preukazu používateľa.
--   3. Samostatná tabuľka zákonných zástupcov.
--   4. Tabuľka tokenov na obnovu hesla pre Laravel.
--
-- password a remember_token zostávajú podľa skriptu 003.
--
-- Skript je určený na jednorazové spustenie.
-- DDL príkazy v MySQL netvoria jednu vratnú transakciu.
-- Pri chybe zastav vykonávanie a skontroluj stav schémy.
-- ============================================================

USE city_bikes;

SET NAMES utf8mb4;
SET time_zone = '+00:00';


-- ============================================================
-- 1. ROZŠÍRENIE USERS
--
-- Nové polia sú nullable, aby existujúce účty zostali platné.
-- Povinný dátum narodenia pri novej registrácii overuje Laravel.
-- ============================================================

ALTER TABLE users

    ADD COLUMN date_of_birth DATE NULL
        COMMENT 'Date of birth; registration age validation is handled by the application',

    -- Textový typ zachová písmená aj úvodné nuly.
    -- NULL znamená, že číslo OP nebolo uvedené.
    ADD COLUMN id_number VARCHAR(8) NULL
        COMMENT 'Optional identity card number',

    -- DB overuje iba dĺžku.
    -- Dohodnutý formát, napr. AB123456, overuje aplikácia.
    ADD CONSTRAINT chk_users_id_number_length
        CHECK (
            id_number IS NULL
            OR CHAR_LENGTH(id_number) = 8
        );


-- ============================================================
-- 2. USER_GUARDIANS
--
-- Zákonný zástupca zákazníka.
--
-- Obsahuje osobné a kontaktné polia podobne ako users.
-- Neobsahuje password, remember_token ani role_id:
-- zástupca týmto záznamom nezískava prihlasovací účet.
--
-- Jeden zákazník môže mať najviac jeden záznam zástupcu.
-- Rovnaký zástupca môže zastupovať viacerých zákazníkov;
-- v tomto jednoduchom modeli má pri každom samostatný záznam.
--
-- Povinnosť zástupcu pri veku 15–17 rokov overuje aplikácia.
-- Samotný cudzí kľúč túto vekovú podmienku nekontroluje.
-- ============================================================

CREATE TABLE user_guardians (

    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

    -- Zákazník, ktorého tento zástupca zastupuje.
    -- Typ sa zhoduje s users.id.
    user_id BIGINT UNSIGNED NOT NULL
        COMMENT 'Customer represented by this guardian',

    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,

    -- Nie je UNIQUE: rodič môže zastupovať viac detí
    -- a pri každom použiť rovnaký e-mail.
    email VARCHAR(254) NOT NULL,

    phone VARCHAR(30) NULL,

    street_address VARCHAR(255) NULL,
    city VARCHAR(100) NULL,
    postal_code VARCHAR(20) NULL,

    date_of_birth DATE NULL,

    -- Podľa dohody nepovinné aj pre zákonného zástupcu.
    id_number VARCHAR(8) NULL
        COMMENT 'Optional identity card number',

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),

    -- Najviac jeden zástupca na zákazníka.
    -- Tento index zároveň podporuje cudzí kľúč user_id.
    UNIQUE KEY uq_user_guardians_user (user_id),

    CONSTRAINT fk_user_guardians_user
        FOREIGN KEY (user_id)
        REFERENCES users (id)
        ON DELETE RESTRICT
        ON UPDATE RESTRICT,

    CONSTRAINT chk_user_guardians_id_number_length
        CHECK (
            id_number IS NULL
            OR CHAR_LENGTH(id_number) = 8
        )

)
ENGINE = InnoDB
DEFAULT CHARSET = utf8mb4
COLLATE = utf8mb4_unicode_ci;


-- ============================================================
-- 3. PASSWORD_RESET_TOKENS
--
-- Štruktúra zodpovedá aktuálnej backendovej migrácii Laravelu.
--
-- Neobsahuje vlastné číselné ID:
-- primárnym kľúčom je e-mail.
--
-- Token vytvára, hashuje a overuje Laravel.
-- Platnosť tokenu a jeho odstránenie rieši aplikácia.
-- ============================================================

CREATE TABLE password_reset_tokens (

    email VARCHAR(255) NOT NULL,

    -- Ukladá sa hash tokenu, nie samotný odkaz z e-mailu.
    token VARCHAR(255) NOT NULL,

    created_at TIMESTAMP NULL DEFAULT NULL,

    PRIMARY KEY (email)

)
ENGINE = InnoDB
DEFAULT CHARSET = utf8mb4
COLLATE = utf8mb4_unicode_ci;


-- ============================================================
-- 4. OVERENIE VÝSLEDNEJ ŠTRUKTÚRY
-- Zobrazujú sa definície, nie osobné údaje používateľov.
-- ============================================================

SHOW COLUMNS FROM users
WHERE Field IN (
    'password',
    'remember_token',
    'date_of_birth',
    'id_number'
);

SHOW CREATE TABLE user_guardians;

SHOW CREATE TABLE password_reset_tokens;