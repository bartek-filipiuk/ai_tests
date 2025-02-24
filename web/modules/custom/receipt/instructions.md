System do zgrywania paragonów w Drupal 11 umożliwia użytkownikom szybkie przesyłanie zdjęć paragonów, które następnie są automatycznie przetwarzane przez GPT-4o OCR za pośrednictwem make.com. Po wgraniu zdjęcia do niestandardowego bloku, plik trafia do webhooka, gdzie jest analizowany, a wynikowy JSON z odczytanymi danymi (data, kwota, produkty) wraca do Drupala i zapisuje się jako wpis typu „Paragon”, przypisany do użytkownika. System pozwala na dalszą automatyzację, np. wysyłanie danych do Google Drive, Airtable czy e-maila, eliminując problem chaotycznego gromadzenia papierowych paragonów. 🚀

Technologia:

Drupal 11 (PHP)
Custom module (niestandardowy moduł w modules/custom/) - modul juz istnieje
Twig (dla UI bloku)
REST API (endpoint w Drupalu do odbioru JSON-a z make.com)
Make.com (integracja do przetwarzania OCR)
🔹 Flow modułu:
2️⃣ Dodajemy custom block, który:

Wyświetla UI w Twig.
Ma pole do wrzucenia zdjęcia (akceptuje obrazy, otwiera aparat na mobile).
Ma przycisk do przesłania zdjęcia.
Wysyła zdjęcie do hardcoded webhooka w make.com.
3️⃣ Make.com przetwarza OCR w GPT-4o i zwraca JSON-a.
4️⃣ Tworzymy routing w Drupalu – POST /api/receipts/process.
Odbiera JSON (data, kwota, produkty z cenami).
Waliduje dane.
Tworzy nowy wpis typu „Paragon” przypisany do użytkownika.
5️⃣ Moduł obsługuje przypisanie paragonu do użytkownika, który go dodał.
6️⃣ Możliwość rozszerzenia – wysyłka do Google Drive, Airtable, e-maila.
💡 Dodatkowe uwagi:

Żadnych hooków w hook_menu() – używamy routing.yml!
Twig powinien być prosty – wrzucanie zdjęć + przycisk.
Plik tymczasowy z paragonem nie musi być trzymany w bazie.
Jeśli JSON od make.com jest błędny – zwracamy 400.
Każdy wpis „Paragon” ma zdjęcie + listę produktów + kwotę + datę.