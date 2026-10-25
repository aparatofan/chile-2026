# Chile 2026 — panel podróży

Wtyczka WordPress z panelem przygotowań do wyprawy do Chile (20 grudnia 2026 – 17 stycznia 2027):
Santiago, Pucón, Patagonia, Atacama i Viña del Mar.

## Co jest na panelu

- **Odliczanie** do wylotu, które w trakcie podróży zamienia się w „Dzień N z 29” z planem na dziś.
- **Trasa** z północy na południe, z przejazdami w kolejności (lot / autobus).
- **Co dalej** — najbliższe punkty planu, zegar Santiago i Warszawy, przelicznik złotych na peso.
- **Do zarezerwowania** — wszystko, czego jeszcze brakuje, z przyciskiem „Zarezerwowane”.
- **Plan dzień po dniu** z etapami, noclegami i wycieczkami. Daty, godziny, statusy i notatki można edytować na panelu.
- **Loty i przejazdy**, z limitami bagażu Turkish Airlines.
- **Lista** w siedmiu kategoriach. Większość punktów odhacza się raz za oboje, a osobiste (paszport, ubezpieczenie, deklaracja SAG) każde osobno. Można dodawać własne punkty.
- **Na miejscu** (numery alarmowe, ambasada, święta), **aplikacje** i **kontakty**.
- Cztery wielkości tekstu (przycisk „Tekst” w rogu).

## Instalacja

1. `./build.sh` — tworzy `mm-chile-2026.zip`.
2. WordPress → Wtyczki → Dodaj nową → Wyślij wtyczkę → wybierz ZIP → Włącz.
3. **Chile 2026 → Ustawienia**: wybierz dwoje podróżnych (każde z kontem w WordPressie), widoczność, kurs i kontakty.
4. Na stronie wstaw shortcode `[chile-dashboard]` (opcjonalnie `skala="standard|duzy|wiekszy|najwiekszy"`).

## Prywatność

To repozytorium jest publiczne, więc w kodzie nie ma numerów paszportów, biletów, adresów, telefonów ani imion gospodarzy.
Te dane wpisuje się w ustawieniach (baza WordPressa). Trafiają do przeglądarki wyłącznie zalogowanym podróżnym —
goście i roboty nie dostają ich w kodzie strony ani przez REST.

Domyślnie cały panel widzą tylko zalogowani podróżni. W ustawieniach można go otworzyć dla wszystkich —
goście widzą wtedy trasę, plan i aplikacje, ale bez listy, notatek, rezerwacji i kontaktów.

## Technicznie

- REST: `mm-chile/v1` — `GET /state`, `POST /tick`, `POST /event`, `POST /custom`. Wymaga zalogowanego podróżnego (lub administratora) i nonce `wp_rest`.
- Stan zapisywany jest w opcji `mmcl_state`, ustawienia w `mmcl_settings` (bez autoload).
- Panel odświeża stan co minutę, więc zmiany jednej osoby widzi druga.
- Dane podróży: `includes/data.php`.
