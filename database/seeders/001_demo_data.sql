-- ============================================================
-- 001_demo_data.sql
-- City Bikes / MySQL 8.4
--
-- Predpoklad:
--   Skripty 001–004 už boli úspešne vykonané.
--
-- Vytvorí:
--   1 administrátora
--   10 zákazníkov
--   10 zákonných zástupcov
--   10 bicyklov
--   10 žiadostí
--   10 prenájmov
--   10 predpisov platieb
--   10 platieb
--   10 dokumentov (iba záznamy, bez PDF súborov)
--   10 neodosielaných testovacích notifikácií
--   10 starých testovacích tokenov obnovy hesla
--
-- Roly customer a admin už existujú zo skriptu 002.
--
-- PRIHLÁSENIE:
--   Admin: demo.admin@example.com
--   Zákazníci: demo.customer01@example.com až
--              demo.customer10@example.com
--
--   Heslo všetkých testovacích účtov: TestBike2026!
--
-- DÔLEŽITÉ:
--   Spusti celý skript v jednom pripojení, iba raz.
--   Pri chybe zastav vykonávanie a spusti ROLLBACK;
--   Nepokračuj po chybe až ku COMMIT.
-- ============================================================

USE city_bikes;

SET NAMES utf8mb4;
SET time_zone = '+00:00';

SET @demo_now = UTC_TIMESTAMP();

-- Bcrypt hash hesla TestBike2026!, náročnosť 12.
-- Do users sa neukladá čisté heslo.
SET @demo_password_hash =
    '$2y$12$NJLxLtdQOX3RF3CSJdIKbOp5ellaVcaIH3lEQbXT2qzRAelHfgInq';


-- ============================================================
-- 1. POMOCNÁ DOČASNÁ TABUĽKA
--
-- Mapuje testovacie údaje na skutočné ID pridelené MySQL.
-- Nepredpokladáme, že tabuľky sú prázdne alebo ID začínajú od 1.
-- ============================================================

CREATE TEMPORARY TABLE tmp_demo_data (
    n INT NOT NULL PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(254) NOT NULL,

    user_id BIGINT UNSIGNED NULL,
    bike_id BIGINT UNSIGNED NULL,
    application_id BIGINT UNSIGNED NULL,
    rental_id BIGINT UNSIGNED NULL,
    payment_request_id BIGINT UNSIGNED NULL,

    rental_status VARCHAR(20) NOT NULL,
    starts_at DATETIME NULL,
    ends_at DATETIME NULL
)
ENGINE = InnoDB
DEFAULT CHARSET = utf8mb4
COLLATE = utf8mb4_unicode_ci;


INSERT INTO tmp_demo_data (
    n,
    first_name,
    last_name,
    email,
    rental_status
)
VALUES
    (1, 'Adam', 'Novák',
        'demo.customer01@example.com', 'reserved'),

    (2, 'Ema', 'Kováčová',
        'demo.customer02@example.com', 'reserved'),

    (3, 'Samuel', 'Horváth',
        'demo.customer03@example.com', 'active'),

    (4, 'Sofia', 'Vargová',
        'demo.customer04@example.com', 'active'),

    (5, 'Jakub', 'Tóth',
        'demo.customer05@example.com', 'overdue'),

    (6, 'Nina', 'Nagyová',
        'demo.customer06@example.com', 'overdue'),

    (7, 'Michal', 'Baláž',
        'demo.customer07@example.com', 'closed'),

    (8, 'Viktória', 'Molnárová',
        'demo.customer08@example.com', 'closed'),

    (9, 'Daniel', 'Polák',
        'demo.customer09@example.com', 'cancelled'),

    (10, 'Laura', 'Urbanová',
        'demo.customer10@example.com', 'cancelled');


-- Termíny prenájmov sú relatívne k spusteniu skriptu.
UPDATE tmp_demo_data
SET
    starts_at = CASE rental_status
        WHEN 'reserved'
            THEN DATE_ADD(@demo_now, INTERVAL 7 DAY)
        WHEN 'active'
            THEN DATE_SUB(@demo_now, INTERVAL 1 DAY)
        WHEN 'overdue'
            THEN DATE_SUB(@demo_now, INTERVAL 10 DAY)
        WHEN 'closed'
            THEN DATE_SUB(@demo_now, INTERVAL 20 DAY)
        WHEN 'cancelled'
            THEN DATE_ADD(@demo_now, INTERVAL 14 DAY)
    END,

    ends_at = CASE rental_status
        WHEN 'reserved'
            THEN DATE_ADD(@demo_now, INTERVAL 10 DAY)
        WHEN 'active'
            THEN DATE_ADD(@demo_now, INTERVAL 3 DAY)
        WHEN 'overdue'
            THEN DATE_SUB(@demo_now, INTERVAL 3 DAY)
        WHEN 'closed'
            THEN DATE_SUB(@demo_now, INTERVAL 15 DAY)
        WHEN 'cancelled'
            THEN DATE_ADD(@demo_now, INTERVAL 17 DAY)
    END;


START TRANSACTION;


-- ============================================================
-- 2. ADMINISTRÁTOR
--
-- E-mail označíme ako overený, aby sa dalo priamo prihlásiť.
-- ============================================================

INSERT INTO users (
    email,
    password,
    first_name,
    last_name,
    role_id,
    is_active,
    email_verified_at,
    date_of_birth
)
VALUES (
    'demo.admin@example.com',
    @demo_password_hash,
    'Demo',
    'Administrátor',
    (SELECT id FROM user_roles WHERE code = 'admin'),
    TRUE,
    @demo_now,
    '1990-01-15'
);

SET @demo_admin_id = LAST_INSERT_ID();


-- ============================================================
-- 3. ZÁKAZNÍCI – 10
--
-- Zákazníci majú 16 rokov, aby sa dali testovať aj zástupcovia.
-- Telefón a číslo OP zostávajú nepovinné a nevyplnené.
-- ============================================================

INSERT INTO users (
    email,
    password,
    first_name,
    last_name,
    street_address,
    city,
    postal_code,
    role_id,
    is_active,
    email_verified_at,
    date_of_birth
)
SELECT
    d.email,
    @demo_password_hash,
    d.first_name,
    d.last_name,
    CONCAT('Testovacia ', d.n),
    'Trnava',
    '917 01',
    (SELECT id FROM user_roles WHERE code = 'customer'),
    TRUE,
    @demo_now,
    DATE_SUB(DATE(@demo_now), INTERVAL 16 YEAR)
FROM tmp_demo_data AS d;


-- Zapamätanie skutočných ID používateľov.
UPDATE tmp_demo_data AS d
JOIN users AS u ON u.email = d.email
SET d.user_id = u.id;


-- ============================================================
-- 4. ZÁKONNÍ ZÁSTUPCOVIA – 10
--
-- Každý zákazník má jedného zástupcu.
-- Zástupca nemá vlastný prihlasovací účet.
-- ============================================================

INSERT INTO user_guardians (
    user_id,
    first_name,
    last_name,
    email,
    street_address,
    city,
    postal_code,
    date_of_birth,
    id_number
)
SELECT
    d.user_id,
    'Testovací zástupca',
    d.last_name,
    CONCAT(
        'demo.guardian',
        LPAD(d.n, 2, '0'),
        '@example.com'
    ),
    CONCAT('Testovacia ', d.n),
    'Trnava',
    '917 01',
    DATE_SUB(DATE(@demo_now), INTERVAL 40 YEAR),
    NULL
FROM tmp_demo_data AS d;


-- ============================================================
-- 5. BICYKLE – 10
--
-- ready znamená technicky pripravený.
-- Obsadenosť sa posudzuje podľa rentals.
-- ============================================================

INSERT INTO bikes (
    inventory_number,
    serial_number,
    model,
    status,
    notes
)
SELECT
    CONCAT('DEMO-BIKE-', LPAD(d.n, 3, '0')),
    CONCAT('DEMO-SERIAL-', LPAD(d.n, 3, '0')),
    CASE
        WHEN MOD(d.n, 2) = 0 THEN 'City Comfort'
        ELSE 'City Classic'
    END,
    'ready',
    'DEMO: fiktívny bicykel na testovanie'
FROM tmp_demo_data AS d;


UPDATE tmp_demo_data AS d
JOIN bikes AS b
    ON b.inventory_number =
        CONCAT('DEMO-BIKE-', LPAD(d.n, 3, '0'))
SET d.bike_id = b.id;


-- ============================================================
-- 6. ŽIADOSTI – 10
--
-- Všetky sú schválené, pretože z každej vznikne prenájom.
-- Dve výsledné rezervácie budú následne zrušené.
-- ============================================================

INSERT INTO rental_applications (
    user_id,
    status,
    submitted_at,
    reviewed_at,
    reviewed_by,
    notes,
    created_at,
    updated_at
)
SELECT
    d.user_id,
    'approved',
    DATE_SUB(@demo_now, INTERVAL 30 DAY),
    DATE_SUB(@demo_now, INTERVAL 29 DAY),
    @demo_admin_id,
    CONCAT('DEMO-APPLICATION-', LPAD(d.n, 3, '0')),
    DATE_SUB(@demo_now, INTERVAL 30 DAY),
    DATE_SUB(@demo_now, INTERVAL 29 DAY)
FROM tmp_demo_data AS d;


UPDATE tmp_demo_data AS d
JOIN rental_applications AS a
    ON a.user_id = d.user_id
    AND a.notes =
        CONCAT('DEMO-APPLICATION-', LPAD(d.n, 3, '0'))
SET d.application_id = a.id;


-- ============================================================
-- 7. PRENÁJMY – 10
--
-- 2 reserved, 2 active, 2 overdue, 2 closed, 2 cancelled.
-- Každý zákazník aj bicykel má iba jeden prenájom.
--
-- Generované ongoing_* stĺpce vypočíta MySQL.
-- ============================================================

INSERT INTO rentals (
    application_id,
    user_id,
    bike_id,
    status,
    starts_at,
    ends_at,
    handed_over_at,
    returned_at,
    notes,
    created_at,
    updated_at
)
SELECT
    d.application_id,
    d.user_id,
    d.bike_id,
    d.rental_status,
    d.starts_at,
    d.ends_at,

    -- Odovzdanie iba pri začatých alebo dokončených prenájmoch.
    CASE
        WHEN d.rental_status IN ('active', 'overdue', 'closed')
            THEN d.starts_at
        ELSE NULL
    END,

    -- Vrátenie iba pri dokončených prenájmoch.
    CASE
        WHEN d.rental_status = 'closed'
            THEN d.ends_at
        ELSE NULL
    END,

    CASE
        WHEN d.rental_status = 'cancelled'
            THEN 'DEMO: rezervácia zrušená na žiadosť zákazníka'
        ELSE 'DEMO: testovací prenájom'
    END,

    DATE_SUB(@demo_now, INTERVAL 28 DAY),
    @demo_now
FROM tmp_demo_data AS d;


UPDATE tmp_demo_data AS d
JOIN rentals AS r
    ON r.application_id = d.application_id
SET d.rental_id = r.id;


-- ============================================================
-- 8. PREDPISY PLATIEB – 10
--
-- 8 zábezpek k nezrušeným prenájmom.
-- 2 nájomné k prvým dvom rezerváciám.
-- Zrušené rezervácie nemajú predpis ani platbu.
--
-- Variabilné symboly: 9900000001 až 9900000010.
-- ============================================================

INSERT INTO payment_requests (
    rental_id,
    type,
    amount,
    currency,
    due_date,
    payment_reference,
    status,
    description,
    created_at,
    updated_at
)
SELECT
    r.id,

    CASE
        WHEN d.n <= 8 THEN 'deposit'
        ELSE 'rental_fee'
    END,

    CASE
        WHEN d.n <= 8 THEN 50.00
        ELSE 15.00
    END,

    'EUR',
    DATE_SUB(DATE(@demo_now), INTERVAL 24 DAY),
    CONCAT('99000000', LPAD(d.n, 2, '0')),
    'issued',
    CONCAT('DEMO: predpis platby ', d.n),
    DATE_SUB(@demo_now, INTERVAL 27 DAY),
    DATE_SUB(@demo_now, INTERVAL 27 DAY)

FROM tmp_demo_data AS d

-- Záznamy 9 a 10 predstavujú predpisy k zákazníkom 1 a 2.
JOIN users AS u
    ON u.email = CONCAT(
        'demo.customer',
        LPAD(
            CASE
                WHEN d.n <= 8 THEN d.n
                ELSE d.n - 8
            END,
            2,
            '0'
        ),
        '@example.com'
    )

JOIN rental_applications AS a
    ON a.user_id = u.id
    AND a.notes = CONCAT(
        'DEMO-APPLICATION-',
        LPAD(
            CASE
                WHEN d.n <= 8 THEN d.n
                ELSE d.n - 8
            END,
            3,
            '0'
        )
    )

JOIN rentals AS r
    ON r.application_id = a.id;


UPDATE tmp_demo_data AS d
JOIN payment_requests AS pr
    ON pr.payment_reference =
        CONCAT('99000000', LPAD(d.n, 2, '0'))
SET d.payment_request_id = pr.id;


-- ============================================================
-- 9. PLATBY – 10
--
-- Každý testovací predpis je plne uhradený.
-- Ide iba o fiktívne záznamy, bez pohybu peňazí.
-- ============================================================

INSERT INTO payments (
    payment_request_id,
    amount,
    paid_at,
    method,
    bank_reference,
    recorded_by,
    notes,
    created_at,
    updated_at
)
SELECT
    pr.id,
    pr.amount,
    DATE_SUB(@demo_now, INTERVAL 25 DAY),

    CASE MOD(d.n, 3)
        WHEN 0 THEN 'bank_transfer'
        WHEN 1 THEN 'cash'
        ELSE 'card'
    END,

    CASE
        WHEN MOD(d.n, 3) = 0
            THEN CONCAT(
                'DEMO-TRANSACTION-',
                LPAD(d.n, 3, '0')
            )
        ELSE NULL
    END,

    @demo_admin_id,
    'DEMO: fiktívna úhrada',
    DATE_SUB(@demo_now, INTERVAL 25 DAY),
    DATE_SUB(@demo_now, INTERVAL 25 DAY)

FROM tmp_demo_data AS d
JOIN payment_requests AS pr
    ON pr.id = d.payment_request_id;


-- ============================================================
-- 10. DOKUMENTY – 10
--
-- Vytvoria sa iba DB záznamy.
-- Reálne PDF súbory na uvedených cestách neexistujú.
-- ============================================================

INSERT INTO documents (
    user_id,
    application_id,
    rental_id,
    type,
    file_name,
    file_path,
    mime_type,
    created_by
)
SELECT
    d.user_id,
    NULL,
    d.rental_id,
    'other',
    CONCAT(
        'demo-document-',
        LPAD(d.n, 3, '0'),
        '.pdf'
    ),
    CONCAT(
        'demo/documents/demo-document-',
        LPAD(d.n, 3, '0'),
        '.pdf'
    ),
    'application/pdf',
    @demo_admin_id
FROM tmp_demo_data AS d;


-- ============================================================
-- 11. NOTIFIKÁCIE – 10
--
-- Stav failed znamená, že nečakajú na odoslanie.
-- Príjemca je vždy vlastníkom príslušného prenájmu.
-- ============================================================

INSERT INTO notifications (
    user_id,
    payment_request_id,
    channel,
    recipient,
    subject,
    body,
    status,
    sent_at,
    error_message
)
SELECT
    r.user_id,
    pr.id,
    'email',
    u.email,
    CONCAT('DEMO: predpis ', pr.payment_reference),
    CONCAT(
        'Testovacia notifikácia k predpisu ',
        pr.payment_reference,
        '. Nejde o skutočnú výzvu na úhradu.'
    ),
    'failed',
    NULL,
    'DEMO: testovací záznam bez odoslania'

FROM tmp_demo_data AS d
JOIN payment_requests AS pr
    ON pr.id = d.payment_request_id
JOIN rentals AS r
    ON r.id = pr.rental_id
JOIN users AS u
    ON u.id = r.user_id;


-- ============================================================
-- 12. TOKENY OBNOVY HESLA – 10
--
-- Staré ilustračné záznamy, nie funkčné resetovacie odkazy.
-- Hash je iba testovacia hodnota.
--
-- Skutočný reset testuj cez formulár Laravelu:
-- aplikácia vytvorí nový token a prepíše tento záznam.
-- ============================================================

INSERT INTO password_reset_tokens (
    email,
    token,
    created_at
)
SELECT
    d.email,
    @demo_password_hash,
    '2000-01-01 00:00:00'
FROM tmp_demo_data AS d;


-- ============================================================
-- 13. POTVRDENIE VLOŽENÝCH DÁT
--
-- Ak niektorý predchádzajúci príkaz zlyhal:
-- nepokračuj týmto COMMIT, ale spusti ROLLBACK.
-- ============================================================

COMMIT;


-- ============================================================
-- 14. KONTROLA TESTOVACÍCH ZÁZNAMOV
--
-- Vo výsledku očakávame users = 11, ostatné = 10.
-- Počítajú sa iba záznamy označené týmto seed skriptom.
-- ============================================================

SELECT
    'users' AS table_name,
    COUNT(*) AS demo_count
FROM users
WHERE email = 'demo.admin@example.com'
   OR email REGEXP '^demo[.]customer(0[1-9]|10)@example[.]com$'

UNION ALL

SELECT 'user_guardians', COUNT(*)
FROM user_guardians
WHERE email REGEXP '^demo[.]guardian(0[1-9]|10)@example[.]com$'

UNION ALL

SELECT 'bikes', COUNT(*)
FROM bikes
WHERE inventory_number
    REGEXP '^DEMO-BIKE-(00[1-9]|010)$'

UNION ALL

SELECT 'rental_applications', COUNT(*)
FROM rental_applications
WHERE notes REGEXP '^DEMO-APPLICATION-(00[1-9]|010)$'

UNION ALL

SELECT 'rentals', COUNT(*)
FROM rentals AS r
JOIN rental_applications AS a ON a.id = r.application_id
WHERE a.notes REGEXP '^DEMO-APPLICATION-(00[1-9]|010)$'

UNION ALL

SELECT 'payment_requests', COUNT(*)
FROM payment_requests
WHERE payment_reference
    REGEXP '^99000000(0[1-9]|10)$'

UNION ALL

SELECT 'payments', COUNT(*)
FROM payments AS p
JOIN payment_requests AS pr ON pr.id = p.payment_request_id
WHERE pr.payment_reference
    REGEXP '^99000000(0[1-9]|10)$'

UNION ALL

SELECT 'documents', COUNT(*)
FROM documents
WHERE file_path
    REGEXP '^demo/documents/demo-document-(00[1-9]|010)[.]pdf$'

UNION ALL

SELECT 'notifications', COUNT(*)
FROM notifications AS n
JOIN payment_requests AS pr ON pr.id = n.payment_request_id
WHERE pr.payment_reference
    REGEXP '^99000000(0[1-9]|10)$'

UNION ALL

SELECT 'password_reset_tokens', COUNT(*)
FROM password_reset_tokens
WHERE email REGEXP '^demo[.]customer(0[1-9]|10)@example[.]com$';


-- Prihlasovacie účty bez zobrazovania hashov hesiel.
SELECT
    u.id,
    u.email,
    u.first_name,
    u.last_name,
    ur.code AS role,
    u.is_active,
    u.email_verified_at
FROM users AS u
JOIN user_roles AS ur ON ur.id = u.role_id
WHERE u.email = 'demo.admin@example.com'
   OR u.email REGEXP '^demo[.]customer(0[1-9]|10)@example[.]com$'
ORDER BY ur.code, u.email;


-- Pomocná tabuľka sa odstráni.
-- Testovacie dáta v aplikačných tabuľkách zostávajú.
DROP TEMPORARY TABLE tmp_demo_data;