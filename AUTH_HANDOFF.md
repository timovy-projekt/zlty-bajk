# Registrácia a prihlásenie – stav pre tím

Vetva: `feature/backend-auth`. Otestované na lokálnej MySQL; hostovaná DB ešte
vyžaduje nižšie uvedené úpravy.

## Hotové

- Registrácia, login/logout, zapamätanie prihlásenia, zmena a reset hesla.
- Overenie e-mailu a zákaznícky/admin dashboard cez spoločný login.
- Minimálny vek 15 rokov. Pri veku 15–17 sa zobrazujú a vyžadujú údaje zákonného zástupcu.
- OP má formát 2 písmená A–Z/a–z a 6 číslic, napríklad `AB123456`. OP zákazníka je voliteľný; OP zástupcu je povinný pri veku 15–17.
- VOP/GDPR: vyžaduje sa zaškrtnutie oboch checkboxov, bez ukladania histórie.

Údaje zákonného zástupcu a číslo OP zákazníka sa zatiaľ **neukladajú**.
E-maily sa lokálne zapisujú do `storage/logs/laravel.log`; pre doručovanie
do schránok treba nastaviť e-mailovú službu.

## Čo treba dohodnúť s DB/backend kolegom

| Oblasť | Potrebná úprava |
| --- | --- |
| Heslo | Lokálne sa používa `password_hash`, hostovaná DB má `password`. Zjednotiť backend a lokálnu schému podľa dohodnutého názvu. |
| Dátum narodenia | Na hostovanej DB doplniť `users.date_of_birth` typu `DATE`, nullable pre existujúce účty. |
| Zákonný zástupca | Navrhnúť uloženie mena, priezviska, OP a vzťahu k zákazníkovi; potom doplniť zápis v backende. |
| OP zákazníka | Doplniť miesto uloženia čísla OP s dĺžkou 8 znakov a následne zápis v backende. |
| Reset hesla | Na hostovanej DB chýba `password_reset_tokens`; pripraviť ho pre funkciu obnovy hesla. |

Hostovaná DB bola skontrolovaná 1. 11. 2026 bez zmeny dát. `remember_token`
už obsahuje; jeho pridanie preto pri integrácii nesmie prebehnúť druhýkrát.

## Pre frontend

- Formuláre: `resources/views/auth/register.blade.php`, `login.blade.php`.
- Backend validácia: `app/Http/Requests/Auth/RegisterRequest.php`, `LoginRequest.php`.
- Zobrazovanie zástupcu: `resources/js/registration-age.js`.
- Zachovať dohodnuté názvy polí; backend nastavuje rolu automaticky na `customer`.
- Doplniť skutočné odkazy na VOP a GDPR namiesto `#`.

Ďalšia funkčná etapa: zákazník odošle **žiadosť o prenájom** a administrátor
ju prijme alebo zamietne. Registrácia účtu nemá samostatné admin schvaľovanie.
