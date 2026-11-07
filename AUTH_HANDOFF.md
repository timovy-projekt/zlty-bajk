# Registrácia a prihlásenie – stav pre tím

Vetva: `feature/backend-integration`. Obsahuje zlúčené zmeny kolegov a opravy backendu.

## Hotové

- Registrácia, login/logout, zapamätanie prihlásenia, zmena a reset hesla.
- Overenie e-mailu a zákaznícky/admin dashboard cez spoločný login.
- Minimálny vek 15 rokov; pri veku 15–17 sa ukladá zákonný zástupca do `user_guardians`.
- Zástupca musí mať meno, priezvisko, e-mail a dátum narodenia. Checkbox rovnakej adresy preberá adresu zákazníka.
- OP zákazníka aj zástupcu je voliteľné. Backend ho validuje a ukladá, ak príde v požiadavke; aktuálny formulár nemá polia OP.
- Formát OP: 2 písmená A–Z/a–z a 6 číslic, napríklad `AB123456`.
- VOP/GDPR: vyžaduje sa zaškrtnutie oboch checkboxov, bez ukladania histórie.
- VOP/GDPR PDF sú verejne dostupné z registrácie; nájomná zmluva vyžaduje prihlásenie a overený e-mail.
- Heslo sa hashuje a ukladá do `users.password`. Zákazník a zástupca sa vytvárajú v jednej transakcii.

## Zostáva pre nasadenie

- Na hostovanej DB zabezpečiť `user_guardians.date_of_birth DATE NOT NULL` podľa novej migrácie `2026_11_07_032019_require_date_of_birth_in_user_guardians_table.php`.
- Ak existujú zástupcovia bez dátumu narodenia, najprv doplniť ich skutočné údaje; migrácia ich nevymýšľa a pri chýbajúcich dátumoch sa zastaví.
- E-maily zostávajú v `storage/logs/laravel.log`; reálne doručovanie sa nastaví pri riešení hostingu.
- Súbeh SQL inštalácie a Laravel migrácií ešte nie je zosúladený. Importované tabuľky a stĺpce sa nesmú vytvoriť druhýkrát.

Pôvodné SQL skripty kolegu zostali bez zmien. Lokálna DB bola aktualizovaná so zachovaním existujúcich dát; do hostovanej DB sa nezapisovalo.

## Overenie

- `php artisan test --compact`: 185 testov, 902 assertions, izolovaná SQLite.
- `php artisan test --compact tests/LocalMysqlAuthenticationTest.php`: 3 scenáre, 132 assertions na lokálnej MySQL. Spúšťať výslovne len pre vývojovú `bajkuj_auth_local`; testy vracajú zápisy späť transakciou a neposielajú skutočné e-maily.
- Pokryté sú registrácia dospelého/maloletého, adresy zástupcu, overenie e-mailu, login/logout, reset hesla, roly a prístup k dokumentom.
- Používateľ manuálne potvrdil prihlásenie do existujúceho účtu; celý tok cez UI zatiaľ nie je manuálne overený.

Ďalšia funkčná etapa: zákazník odošle **žiadosť o prenájom** a administrátor
ju prijme alebo zamietne. Registrácia účtu nemá samostatné admin schvaľovanie.
