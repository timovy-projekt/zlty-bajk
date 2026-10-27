# zlty-bajk - Téma 4: Bajkuj v meste



 Ide o nahradenie / úpravu existujúceho webu [Bajkuj v meste](https://bajkujvmeste.trnava.sk/), + doplniť funkčným riešením na:
     
1. **registráciu záujemcov o bajk** (nahradiť formulár, ktorý je implementovaný riešením, ktoré ukladá údaje do databázy a ďalej s nimi pracuje, registrácia používateľov na webke, prístup k vlastným údajom a dokumentom)
2. **registráciu prenájmov bajkov**
3. **evidenciu úhrad** (ak by sa dalo automatizovane z výpisov, bol by to bonus)
4. **zasielanie rôznych notifikácií a výziev na úhradu** (s vygenerovaným predpisom úhrady s QR kódom)
5.  **generovanie, ukladanie a tlač zmluvy, protokolu, všeobecných podmienok**, 
6.  **administrátorské rozhranie** s prístupom k všetkým dátam a dokumentom


## Stiahni Laravel Herd 
https://herd.laravel.com/windows

nástroj obsahuje všetko potrebné pre prácu s Laravel Frameworkom
- PHP
- Webový server
- Composer - nástroj na sťahovanie PHP knižníc a závislostí
- Laravel Installer
## Naklonovať projekt z GitHubu

```
cd ~/Herd
git clone https://github.com/timovy-projekt/zlty-bajk.git
cd zlty-bajk

```
- ak naklonujte do ineho priecinku ako Herd, treba spustit v terminali prikaz ktory prida projekt do Laravel Herd. Ak je priamo v Herd priecinku, projekt sa nájde automaticky.

```
link herd
  ```

## TO DO - po naklonovani projektu  v priecniku Herd/zlty-bajk spustit v terminali tieto prikazy (postupne)
- je to potrebne lebo niektore veci sa z bezpecnostnych dovodov alebo kvoli velkosti kniznich (PHP a JS kniznice) nepushuju do gitHubu. Popis co robia jednotlive prikazy vid dole.

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm install
npm run build
```

Čo robia jednotlivé príkazy

1. **composer install**
Composer je správca PHP balíčkov. Prečíta zoznam knižníc, ktoré projekt potrebuje (súbor composer.json a composer.lock), a stiahne ich do priečinka vendor/. Laravel samotný je takáto knižnica, takže bez tohto kroku projekt nepobeží.

2. **cp .env.example .env**
Skopíruje súbor .env.example (vzor nastavení, ktorý je v Gite) do nového súboru .env. V .env je tvoje lokálne nastavenie: názov aplikácie, databáza, tajný kľúč. Každý v tíme má vlastný, preto sa nepushuje. Na Windows v cmd sa použije copy, ale v Git Bashi funguje cp.

3. **php artisan key:generate**
artisan je Laravelov nástroj na príkazový riadok. Tento príkaz vygeneruje náhodný tajný kľúč aplikácie a zapíše ho do .env. Laravel ho používa na šifrovanie (napríklad sessions a cookies). Bez neho aplikácia hlási chybu.

4. **php artisan migrate**
Vytvorí v databáze tabuľky podľa migrácií (súbory v database/migrations). Pri Breeze sa tým vytvorí napríklad tabuľka users, takže funguje registrácia a prihlásenie. Ak sa opýta na vytvorenie súbru databázy treba potvrdiť. (predvolene sqlite)

5. **npm install**
npm je správca balíčkov pre JavaScript. Stiahne nástroje na frontend (Vite, Tailwind CSS) do priečinka node_modules/. Je to obdoba composer install, ale pre frontend.

6. **npm run build**
Vezme zdrojové štýly a skripty (resources/css, resources/js) a vytvorí z nich hotové súbory, ktoré načíta prehliadač. Bez tohto kroku stránka vyzerá nenaštýlovaná. Ak chceš pri úpravách vidieť zmeny hneď, použi namiesto toho npm run dev.


## Databáza
prednastavená je lokálna sql databáza. Ak chcete pracovat s mysql nastavte si lokálne v súbro .env (skrytý súbor) tieto hodnoty
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=city_bikes
DB_USERNAME=root
DB_PASSWORD=

SESSION_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=sync
```

Test DB:
``` php artisan config:clear``` zmaze nakešovany config subor a načita .env znova
```php artisan tinker``` Laravel terminal
```Schema::getTableListing()``` Laravel prikaz alternativne ```DB::select('show tables')``` SQL prikaz
Ak dostanes zoznam tabuliek spojenie funguje.

## Nakoniec spusti aplikaciu lokalne v browseri
http://zlty-bajk.test

mal by si vidiet Laravel uvodnu stranku Lets get started.
