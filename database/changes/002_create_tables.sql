-- ============================================================
-- 000_create_tables.sql
-- City Bikes / MySQL 8.4 / InnoDB
--
-- Databáza city_bikes už musí existovať a byť prázdna.
-- Skript vytvorí 9 tabuliek a základné používateľské roly.
--
-- Skript neobsahuje mazanie existujúcich tabuliek.
-- Nie je určený na opakované spustenie ani aktualizáciu
-- už vytvorenej schémy.
--
-- DDL príkazy v MySQL netvoria jednu vratnú transakciu.
-- Pri chybe zastav vykonávanie a skontroluj výsledný stav.
--
-- Časy ukladáme v UTC. Rovnaké nastavenie musí používať
-- každé pripojenie aplikácie.
--
-- Kontrolu prekrývania rezervácií musí zabezpečiť
-- samostatná transakčná procedúra.
--
-- Skript zatiaľ nebol vykonaný na MySQL serveri.
-- ============================================================

USE city_bikes;

SET NAMES utf8mb4;
SET time_zone = '+00:00';


-- ============================================================
-- 1. USER_ROLES
-- Číselník rolí. Jeden používateľ má jednu rolu.
--
-- code = stabilný kód používaný aplikáciou
-- name = čitateľný názov
-- ============================================================

CREATE TABLE user_roles (
    id SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    code VARCHAR(30) NOT NULL,
    name VARCHAR(100) NOT NULL,

    PRIMARY KEY (id),

    UNIQUE KEY uq_user_roles_code (code)
)
ENGINE = InnoDB
DEFAULT CHARSET = utf8mb4
COLLATE = utf8mb4_unicode_ci;


-- Pevné ID rolí.
-- Predvolená rola používateľa bude 1 = customer.

INSERT INTO user_roles (id, code, name)
VALUES
    (1, 'customer', 'Customer'),
    (2, 'admin', 'Administrator');


-- ============================================================
-- 2. USERS
-- Účty zákazníkov a administrátorov.
--
-- Heslo musí aplikácia uložiť ako hash.
-- Telefón a adresa sú pri registrácii voliteľné.
-- Povinnosť ich doplnenia pred prenájmom rieši aplikácia.
--
-- Účty s históriou deaktivujeme cez is_active.
-- ============================================================

CREATE TABLE users (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

    email VARCHAR(254) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,

    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    phone VARCHAR(30) NULL,

    street_address VARCHAR(255) NULL,
    city VARCHAR(100) NULL,
    postal_code VARCHAR(20) NULL,

    role_id SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,

    email_verified_at DATETIME NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),

    UNIQUE KEY uq_users_email (email),

    KEY ix_users_role (role_id),

    CONSTRAINT fk_users_role
        FOREIGN KEY (role_id)
        REFERENCES user_roles (id),

    CONSTRAINT chk_users_is_active
        CHECK (is_active IN (0, 1))
)
ENGINE = InnoDB
DEFAULT CHARSET = utf8mb4
COLLATE = utf8mb4_unicode_ci;


-- ============================================================
-- 3. RENTAL_APPLICATIONS
-- Žiadosti o prenájom bicykla.
--
-- submitted = podaná
-- approved  = schválená
-- rejected  = zamietnutá
-- cancelled = zrušená
--
-- reviewed_by označuje používateľa, ktorý žiadosť posúdil.
-- Jeho oprávnenia musí overiť aplikácia/procedúra.
-- ============================================================

CREATE TABLE rental_applications (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

    user_id BIGINT UNSIGNED NOT NULL,

    status VARCHAR(20) NOT NULL DEFAULT 'submitted',

    submitted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    reviewed_at DATETIME NULL,
    reviewed_by BIGINT UNSIGNED NULL,

    notes TEXT NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),

    -- Cieľ zložených cudzích kľúčov.
    -- Umožňuje kontrolovať aj vlastníka žiadosti.
    UNIQUE KEY uq_applications_id_user (id, user_id),

    KEY ix_applications_user_status (
        user_id,
        status
    ),

    KEY ix_applications_status_submitted (
        status,
        submitted_at
    ),

    CONSTRAINT fk_applications_user
        FOREIGN KEY (user_id)
        REFERENCES users (id),

    CONSTRAINT fk_applications_reviewer
        FOREIGN KEY (reviewed_by)
        REFERENCES users (id),

    CONSTRAINT chk_applications_status
        CHECK (
            status IN (
                'submitted',
                'approved',
                'rejected',
                'cancelled'
            )
        ),

    CONSTRAINT chk_applications_review_time
        CHECK (
            reviewed_at IS NULL
            OR reviewed_at >= submitted_at
        )
)
ENGINE = InnoDB
DEFAULT CHARSET = utf8mb4
COLLATE = utf8mb4_unicode_ci;


-- ============================================================
-- 4. BIKES
-- Evidencia bicyklov.
--
-- inventory_number = interné evidenčné číslo
-- serial_number    = výrobné číslo
--
-- Technický/evidenčný stav:
-- ready       = pripravený na používanie
-- maintenance = v servise
-- retired     = vyradený
-- lost        = stratený
--
-- ready neznamená automaticky voľný.
-- Dostupnosť v termíne sa zisťuje aj cez rentals.
-- ============================================================

CREATE TABLE bikes (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

    inventory_number VARCHAR(50) NOT NULL,
    serial_number VARCHAR(100) NULL,

    model VARCHAR(150) NULL,

    status VARCHAR(20) NOT NULL DEFAULT 'ready',

    notes TEXT NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),

    UNIQUE KEY uq_bikes_inventory_number (inventory_number),

    UNIQUE KEY uq_bikes_serial_number (serial_number),

    KEY ix_bikes_status (status),

    CONSTRAINT chk_bikes_status
        CHECK (
            status IN (
                'ready',
                'maintenance',
                'retired',
                'lost'
            )
        )
)
ENGINE = InnoDB
DEFAULT CHARSET = utf8mb4
COLLATE = utf8mb4_unicode_ci;


-- ============================================================
-- 5. RENTALS
-- Rezervácie a prenájmy konkrétnych bicyklov.
--
-- reserved  = plánovaná rezervácia
-- active    = bicykel odovzdaný používateľovi
-- overdue   = prebiehajúci prenájom po termíne
-- closed    = dokončený prenájom s vráteným bicyklom
-- cancelled = zrušená rezervácia
--
-- starts_at / ends_at = plánovaný interval
-- handed_over_at     = skutočné odovzdanie
-- returned_at        = skutočné vrátenie
--
-- Jedna žiadosť vytvorí najviac jeden prenájom.
--
-- UNIQUE indexy zabránia:
-- * dvom active/overdue prenájmom jedného používateľa,
-- * dvom active/overdue prenájmom jedného bicykla.
--
-- Budúcich rezervácií môže byť viac.
-- Ich časové prekrývanie musí kontrolovať procedúra.
--
-- Požadovaná kombinácia stavov a časov:
--
-- reserved:
--   handed_over_at = NULL
--   returned_at    = NULL
--
-- active / overdue:
--   handed_over_at je vyplnené
--   returned_at    = NULL
--
-- closed:
--   handed_over_at je vyplnené
--   returned_at je vyplnené
--   returned_at >= handed_over_at
--
-- cancelled:
--   handed_over_at = NULL
--   returned_at    = NULL
-- ============================================================

CREATE TABLE rentals (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

    application_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    bike_id BIGINT UNSIGNED NOT NULL,

    status VARCHAR(20) NOT NULL DEFAULT 'reserved',

    starts_at DATETIME NOT NULL,
    ends_at DATETIME NOT NULL,

    handed_over_at DATETIME NULL,
    returned_at DATETIME NULL,

    notes TEXT NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    -- Vypočítava MySQL; aplikácia do stĺpcov nezapisuje.
    --
    -- Pri active/overdue obsahujú príslušné ID.
    -- Pri ostatných stavoch obsahujú NULL.
    --
    -- UNIQUE index povoľuje viac hodnôt NULL,
    -- ale nedovolí opakované neprázdne ID.

    ongoing_user_id BIGINT UNSIGNED
        GENERATED ALWAYS AS (
            CASE
                WHEN status IN ('active', 'overdue')
                    THEN user_id
                ELSE NULL
            END
        ) VIRTUAL,

    ongoing_bike_id BIGINT UNSIGNED
        GENERATED ALWAYS AS (
            CASE
                WHEN status IN ('active', 'overdue')
                    THEN bike_id
                ELSE NULL
            END
        ) VIRTUAL,

    PRIMARY KEY (id),

    UNIQUE KEY uq_rentals_application (application_id),

    -- Cieľ cudzieho kľúča dokumentov.
    UNIQUE KEY uq_rentals_id_user (id, user_id),

    UNIQUE KEY uq_rentals_ongoing_user (ongoing_user_id),

    UNIQUE KEY uq_rentals_ongoing_bike (ongoing_bike_id),

    -- Podporuje zložený cudzí kľúč
    -- fk_rentals_application_user.
    KEY ix_rentals_application_user (
        application_id,
        user_id
    ),

    KEY ix_rentals_user_created (
        user_id,
        created_at
    ),

    KEY ix_rentals_bike_created (
        bike_id,
        created_at
    ),

    KEY ix_rentals_status_end (
        status,
        ends_at
    ),

    -- Indexy na vyhľadávanie termínových konfliktov.
    KEY ix_rentals_user_calendar (
        user_id,
        status,
        starts_at,
        ends_at
    ),

    KEY ix_rentals_bike_calendar (
        bike_id,
        status,
        starts_at,
        ends_at
    ),

    -- Kontroluje existenciu žiadosti aj jej vlastníka.
    CONSTRAINT fk_rentals_application_user
        FOREIGN KEY (application_id, user_id)
        REFERENCES rental_applications (id, user_id),

    CONSTRAINT fk_rentals_user
        FOREIGN KEY (user_id)
        REFERENCES users (id),

    CONSTRAINT fk_rentals_bike
        FOREIGN KEY (bike_id)
        REFERENCES bikes (id),

    CONSTRAINT chk_rentals_status
        CHECK (
            status IN (
                'reserved',
                'active',
                'overdue',
                'closed',
                'cancelled'
            )
        ),

    -- Plánovaný koniec musí byť neskôr ako začiatok.
    CONSTRAINT chk_rentals_dates
        CHECK (ends_at > starts_at),

    -- Vrátenie vyžaduje odovzdanie a nesmie mu predchádzať.
    CONSTRAINT chk_rentals_return
        CHECK (
            returned_at IS NULL
            OR (
                handed_over_at IS NOT NULL
                AND returned_at >= handed_over_at
            )
        ),

    -- Prebiehajúci prenájom musí mať čas odovzdania.
    CONSTRAINT chk_rentals_handover
        CHECK (
            status NOT IN ('active', 'overdue')
            OR handed_over_at IS NOT NULL
        ),

    -- Rezervácia ani prebiehajúci prenájom nemajú vrátenie.
    CONSTRAINT chk_rentals_ongoing_return
        CHECK (
            status NOT IN ('reserved', 'active', 'overdue')
            OR returned_at IS NULL
        ),

    -- Rezervovaný ani zrušený prenájom nesmie mať odovzdanie.
    CONSTRAINT chk_rentals_not_started_handover
        CHECK (
            status NOT IN ('reserved', 'cancelled')
            OR handed_over_at IS NULL
        ),

    -- Dokončený prenájom musí mať odovzdanie aj vrátenie.
    CONSTRAINT chk_rentals_closed_return
        CHECK (
            status <> 'closed'
            OR (
                handed_over_at IS NOT NULL
                AND returned_at IS NOT NULL
            )
        )
)
ENGINE = InnoDB
DEFAULT CHARSET = utf8mb4
COLLATE = utf8mb4_unicode_ci;


-- ============================================================
-- 6. PAYMENT_REQUESTS
-- Predpisy úhrad: čo treba zaplatiť a dokedy.
--
-- Jeden prenájom môže mať viac predpisov.
--
-- type:
-- rental_fee = nájomné
-- deposit    = zábezpeka
-- damage_fee = úhrada škody
-- other      = iná úhrada
--
-- status:
-- issued    = vystavený
-- cancelled = zrušený
--
-- Zaplatenie odvodzujeme zo súčtu prijatých platieb.
-- Prvá verzia podporuje iba EUR.
-- ============================================================

CREATE TABLE payment_requests (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

    rental_id BIGINT UNSIGNED NOT NULL,

    type VARCHAR(30) NOT NULL,

    amount DECIMAL(10,2) NOT NULL,
    currency CHAR(3) NOT NULL DEFAULT 'EUR',

    due_date DATE NOT NULL,

    -- Variabilný symbol ako text zachová úvodné nuly.
    -- Formát 1 až 10 číslic kontroluje aplikácia/procedúra.
    payment_reference VARCHAR(10) NOT NULL
        COMMENT 'Unique numeric variable symbol',

    status VARCHAR(20) NOT NULL DEFAULT 'issued',

    description VARCHAR(255) NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),

    UNIQUE KEY uq_payment_requests_reference (
        payment_reference
    ),

    KEY ix_payment_requests_rental (rental_id),

    KEY ix_payment_requests_status_due (
        status,
        due_date
    ),

    CONSTRAINT fk_payment_requests_rental
        FOREIGN KEY (rental_id)
        REFERENCES rentals (id),

    CONSTRAINT chk_payment_requests_type
        CHECK (
            type IN (
                'rental_fee',
                'deposit',
                'damage_fee',
                'other'
            )
        ),

    CONSTRAINT chk_payment_requests_amount
        CHECK (amount > 0),

    CONSTRAINT chk_payment_requests_currency
        CHECK (currency = 'EUR'),

    CONSTRAINT chk_payment_requests_status
        CHECK (
            status IN (
                'issued',
                'cancelled'
            )
        )
)
ENGINE = InnoDB
DEFAULT CHARSET = utf8mb4
COLLATE = utf8mb4_unicode_ci;


-- ============================================================
-- 7. PAYMENTS
-- Skutočne prijaté platby.
--
-- Jeden predpis môže byť zaplatený viacerými platbami.
-- Napríklad predpis 50 EUR a dve platby po 25 EUR.
--
-- bank_reference = referencia bankovej transakcie
-- recorded_by    = používateľ, ktorý platbu zaevidoval
--
-- Mena sa preberá z predpisu; v tejto verzii iba EUR.
-- Refundácie a vrátenie zábezpeky zatiaľ nie sú riešené.
-- Automatický import a párovanie výpisov zatiaľ nie sú riešené.
-- ============================================================

CREATE TABLE payments (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

    payment_request_id BIGINT UNSIGNED NOT NULL,

    amount DECIMAL(10,2) NOT NULL,
    paid_at DATETIME NOT NULL,

    method VARCHAR(20) NOT NULL DEFAULT 'bank_transfer',

    bank_reference VARCHAR(255) NULL,
    recorded_by BIGINT UNSIGNED NULL,

    notes TEXT NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),

    KEY ix_payments_request_paid (
        payment_request_id,
        paid_at
    ),

    CONSTRAINT fk_payments_request
        FOREIGN KEY (payment_request_id)
        REFERENCES payment_requests (id),

    CONSTRAINT fk_payments_recorder
        FOREIGN KEY (recorded_by)
        REFERENCES users (id),

    CONSTRAINT chk_payments_amount
        CHECK (amount > 0),

    CONSTRAINT chk_payments_method
        CHECK (
            method IN (
                'bank_transfer',
                'cash',
                'card'
            )
        )
)
ENGINE = InnoDB
DEFAULT CHARSET = utf8mb4
COLLATE = utf8mb4_unicode_ci;


-- ============================================================
-- 8. DOCUMENTS
-- Dokumenty a odkazy na ich súbory.
--
-- PDF sa neukladá priamo do DB.
-- file_path obsahuje cestu/kľúč do súkromného úložiska.
--
-- Dokument patrí práve jednej:
-- * žiadosti,
-- * alebo položke prenájmu.
--
-- Zložené cudzie kľúče kontrolujú aj vlastníka dokumentu.
-- Novú verziu dokumentu ukladáme ako nový riadok a súbor.
--
-- terms je dokument priradený ku konkrétnej žiadosti/prenájmu.
-- Nejde o globálny číselník všeobecných podmienok.
-- ============================================================

CREATE TABLE documents (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

    user_id BIGINT UNSIGNED NOT NULL,

    application_id BIGINT UNSIGNED NULL,
    rental_id BIGINT UNSIGNED NULL,

    type VARCHAR(30) NOT NULL,

    file_name VARCHAR(255) NOT NULL,

    file_path VARCHAR(1024) NOT NULL
        COMMENT 'Private storage key; authorize access in application',

    mime_type VARCHAR(100) NOT NULL DEFAULT 'application/pdf',

    created_by BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),

    KEY ix_documents_user_created (
        user_id,
        created_at
    ),

    KEY ix_documents_application_user (
        application_id,
        user_id
    ),

    KEY ix_documents_rental_user (
        rental_id,
        user_id
    ),

    CONSTRAINT fk_documents_user
        FOREIGN KEY (user_id)
        REFERENCES users (id),

    CONSTRAINT fk_documents_application_user
        FOREIGN KEY (application_id, user_id)
        REFERENCES rental_applications (id, user_id),

    CONSTRAINT fk_documents_rental_user
        FOREIGN KEY (rental_id, user_id)
        REFERENCES rentals (id, user_id),

    CONSTRAINT fk_documents_creator
        FOREIGN KEY (created_by)
        REFERENCES users (id),

    -- Vyplnená musí byť práve jedna nadradená väzba.
    CONSTRAINT chk_documents_parent
        CHECK (
            (
                application_id IS NOT NULL
                AND rental_id IS NULL
            )
            OR
            (
                application_id IS NULL
                AND rental_id IS NOT NULL
            )
        ),

    CONSTRAINT chk_documents_type
        CHECK (
            type IN (
                'contract',
                'handover_report',
                'return_report',
                'terms',
                'other'
            )
        )
)
ENGINE = InnoDB
DEFAULT CHARSET = utf8mb4
COLLATE = utf8mb4_unicode_ci;


-- ============================================================
-- 9. NOTIFICATIONS
-- Správy čakajúce na odoslanie a výsledok ich odoslania.
--
-- channel:
-- email = e-mail
-- sms   = SMS
--
-- status:
-- pending = čaká na odoslanie
-- sent    = odoslaná
-- failed  = odoslanie zlyhalo
--
-- recipient zachová adresu/číslo použité pre danú správu,
-- aj keby si používateľ neskôr zmenil kontaktné údaje.
--
-- payment_request_id je voliteľné:
-- vyplní sa napríklad pri výzve na úhradu.
--
-- POZOR:
-- Cudzie kľúče overujú existenciu používateľa a predpisu,
-- ale nekontrolujú, že predpis patrí danému používateľovi.
-- Túto väzbu musí overiť aplikácia/procedúra cez rentals.
--
-- Samotná tabuľka správy neodosiela.
-- ============================================================

CREATE TABLE notifications (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

    user_id BIGINT UNSIGNED NOT NULL,
    payment_request_id BIGINT UNSIGNED NULL,

    channel VARCHAR(20) NOT NULL DEFAULT 'email',

    recipient VARCHAR(254) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    body TEXT NOT NULL,

    status VARCHAR(20) NOT NULL DEFAULT 'pending',

    sent_at DATETIME NULL,
    error_message TEXT NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),

    KEY ix_notifications_user_created (
        user_id,
        created_at
    ),

    KEY ix_notifications_status_created (
        status,
        created_at
    ),

    CONSTRAINT fk_notifications_user
        FOREIGN KEY (user_id)
        REFERENCES users (id),

    CONSTRAINT fk_notifications_request
        FOREIGN KEY (payment_request_id)
        REFERENCES payment_requests (id),

    CONSTRAINT chk_notifications_channel
        CHECK (
            channel IN (
                'email',
                'sms'
            )
        ),

    CONSTRAINT chk_notifications_status
        CHECK (
            status IN (
                'pending',
                'sent',
                'failed'
            )
        ),

    CONSTRAINT chk_notifications_sent
        CHECK (
            status <> 'sent'
            OR sent_at IS NOT NULL
        )
)
ENGINE = InnoDB
DEFAULT CHARSET = utf8mb4
COLLATE = utf8mb4_unicode_ci;


-- ============================================================
-- KALENDÁROVÉ REZERVÁCIE – NÁSLEDNÁ PROCEDÚRA
--
-- Tento skript vytvára iba tabuľky.
-- Kontrola časového prekrývania tu ešte NIE JE implementovaná.
--
-- Rovnakú kontrolu musia používať operácie:
-- * vytvorenie rezervácie,
-- * zmena termínu,
-- * zmena používateľa alebo bicykla,
-- * obnovenie zrušenej rezervácie,
-- * zmeny stavov, ktoré ovplyvňujú obsadenosť.
--
-- Postup:
--
-- 1. Začať transakciu.
--
-- 2. Zamknúť dotknutých používateľov:
--    SELECT ... FOR UPDATE.
--
-- 3. Zamknúť dotknuté bicykle:
--    SELECT ... FOR UPDATE.
--
--    V každej tabuľke zamykať podľa ID vzostupne.
--    Pri prepriradení zamknúť pôvodné aj nové objekty.
--    Všetky procedúry musia dodržiavať rovnaké poradie.
--
-- 4. Aktuálnym zamykajúcim čítaním skontrolovať rentals
--    pre používateľa aj bicykel v stavoch:
--
--    reserved / active / overdue
--
--    Pri úprave vynechať upravovaný záznam.
--
--    Konflikt plánovaných intervalov:
--
--    existing.starts_at < requested.ends_at
--    AND existing.ends_at > requested.starts_at
--
--    Susedné intervaly sú povolené:
--    jeden môže končiť presne v čase začiatku ďalšieho.
--
-- 5. Pri konflikte odmietnuť operáciu a vykonať ROLLBACK.
--    Bez konfliktu uložiť zmenu a vykonať COMMIT.
--
-- Pri odovzdaní navyše overiť:
-- * predchádzajúci prenájom je skutočne ukončený,
-- * bicykel bol skutočne vrátený,
-- * bicykel je technicky pripravený.
--
-- Plánovaný ends_at sám osebe nepotvrdzuje vrátenie bicykla.
--
-- UNIQUE indexy samostatne zabránia dvom active/overdue
-- prenájmom rovnakého používateľa alebo bicykla.
--
-- Ak má byť procedúra jedinou cestou zápisu, aplikačný
-- DB účet nesmie mať priamy zápis do rentals.
-- ============================================================


-- ============================================================
-- ĎALŠIE PRAVIDLÁ PRE APLIKÁCIU / PROCEDÚRY
--
-- * Administrátorské operácie musia overovať rolu používateľa.
--
-- * Prenájom musí vychádzať zo schválenej žiadosti.
--
-- * Povolené prechody stavov treba kontrolovať samostatne.
--   CHECK kontroluje výsledný riadok, nie históriu prechodov.
--
-- * Pri odovzdaní nastavovať status = 'active'
--   a handed_over_at v jednom UPDATE.
--
-- * Pri vrátení nastavovať status = 'closed'
--   a returned_at v jednom UPDATE.
--
-- * overdue sa musí nastavovať aplikáciou alebo úlohou.
--   Plynutie času samo hodnotu status nezmení.
--
-- * Variabilný symbol musí obsahovať 1 až 10 číslic.
--
-- * Pri vytváraní notifikácie k predpisu overiť:
--
--   payment_requests.rental_id -> rentals.id
--   rentals.user_id = notifications.user_id
--
-- * Zaplatenie predpisu odvodzovať zo SUM(payments.amount).
--   Pri výpočtoch zohľadňovať zrušené predpisy.
--
-- * Refundácie a vrátenie zábezpeky doplní ďalšia verzia.
--
-- * Heslá hashovať v aplikácii.
--
-- * Dokumenty ukladať súkromne a autorizovať ich sťahovanie.
--
-- * Cudzie kľúče nemajú ON DELETE CASCADE.
--   Vymazanie referencovaných záznamov sa preto zablokuje.
-- ============================================================


-- Výsledný zoznam tabuliek v databáze.
SHOW TABLES;