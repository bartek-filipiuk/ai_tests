# Dokumentacja Modułu Together API Integration

## 1. Ogólne Wytyczne

* **Nazwa Modułu:** Together API Integration (istnieje pusty moduł)
* **Nazwa Maszynowa:** `together_api`
* **Nazwa Wyświetlana:** Together API Integration
* **Technologie:**
  * CMS: Drupal 10+
  * Język Programowania: PHP 8.2+
  * API: Together API
* **Standardy Kodowania:**
  * Stosowanie standardów kodowania Drupal (Drupal Coding Standards).
  * Używanie PHPDoc do dokumentowania kodu.
* **Bezpieczeństwo:**
  * Walidacja i sanitizacja wszystkich danych wejściowych.
  * Bezpieczne przechowywanie kluczy API i innych poufnych informacji.
* **Wydajność:**
  * Implementacja cache tam, gdzie jest to możliwe.
  * Optymalizacja zapytań do Together API.


## 2. Cel Projektu

### 2.1. Cele Ogólne

Stworzenie modułu Drupal 10+, który integruje się z Together API, umożliwiając komunikację z modelami AI. Moduł ma umożliwiać:

* Konfigurację klucza API Together.
* Wysyłanie zapytań do Together API.
* Przetwarzanie i prezentację odpowiedzi z Together API w interfejsie Drupal.

### 2.2. Cele Szczegółowe

* **Implementacja Endpointów Together API:**
  * Lista Wszystkich Modeli
  * Create Chat Completion
  * Create Completion
  * Create Image
* **Dokumentacja Together API:**
  * Korzystanie z oficjalnej dokumentacji Together API podczas kodowania.
  * Zgodność implementacji endpointów ze specyfikacją API.
* **Podstrona dla Administratora:**
  * Dedykowana podstrona w panelu administratora Drupal do testowania endpointów.
  * Formularze do wprowadzania danych i wyświetlania odpowiedzi z API.


## 3. Pliki Referencyjne

* **Pusty Moduł Drupal:** Struktura katalogów i plików zgodna z wymaganiami Drupal.
* **Pliki podstawowe:**
  * `together_api.info.yml`
  * `together_api.routing.yml`
  * `together_api.services.yml`
  * `src/Controller/`
  * `src/Form/`
  * `src/Service/`
* **Dokumentacja Together API:** (linki do dokumentacji poszczególnych endpointów)
  * Lista Wszystkich Modeli: docs/together_api/ListAllModels.md
  * Create Chat Completion: docs/together_api/CreateChatCompletion.md
  * Create Completion: docs/together_api/CreateCompletion.md
  * Create Image: docs/together_api/CreateImage.md


## 4. Dokumentacja

* **`README.md`:**
  * Opis modułu.
  * Instrukcje instalacji.
  * Konfiguracja modułu.
  * Przykłady użycia.
* **Dokumentacja Kodowa:**
  * Komentarze w kodzie zgodne z PHPDoc.
  * Opisy funkcji, klas i metod.
* **Instrukcje Konfiguracji:**
  * Jak ustawić klucz API Together.
  * Dostępne opcje konfiguracji w interfejsie Drupal.
* **Instrukcje Użytkowania:**
  * Jak korzystać z podstrony testowej dla administratora.
  * Opis dostępnych endpointów i parametrów.


## 5. Wytyczne Techniczne

### a. Architektoniczna Wizja

* **Użycie Guzzle:**  Komunikacja z Together API za pomocą Guzzle HTTP Client (`http_client` service).
* **Usługi i Dependency Injection:** Definiowanie usług w `together_api.services.yml`. Wykorzystanie Dependency Injection.
* **Konfiguracja:** Użycie Drupal Configuration API (`config.factory`).
* **Logowanie i Obsługa Błędów:** Drupal Logger API (`logger.factory`), obsługa wyjątków Guzzle, komunikaty błędów (`drupal_set_message()`).
* **Tłumaczenia i Lokalizacja:**  `$this->t('tekst')` lub `t('tekst')`.
* **Cache:** Drupal Cache API.
* **Standardy Kodowania:** Zgodność z Drupal Coding Standards (PSR-12 + Drupal). PHP_CodeSniffer.
* **Uprawnienia i Bezpieczeństwo:** Definicja uprawnień w `together_api.permissions.yml`.  `\Drupal::currentUser()->hasPermission()`.
* **Testy:** Testy jednostkowe i funkcjonalne (PHPUnit i narzędzia testowe Drupal). Mockowanie odpowiedzi API.
* **Interfejs Użytkownika:** Drupal Form API, standardowe klasy CSS Drupal.
* **Style i Skrypty:** Drupal Libraries w pliku `.libraries.yml`.
* **Bezpieczeństwo Danych:** Sanityzacja danych wejściowych (np. `Xss::filter()`). Ochrona przed XSS, CSRF.


### b. Przewidziana Struktura Kodu i Plików

```
together_api/
├── together_api.info.yml
├── together_api.routing.yml
├── together_api.services.yml
├── together_api.permissions.yml
├── together_api.libraries.yml
├── src/
│   ├── Controller/
│   │   ├── AdminTestController.php
│   │   └── TogetherApiController.php
│   ├── Form/
│   │   ├── SettingsForm.php
│   │   └── TestEndpointForm.php
│   ├── Service/
│   │   └── Client.php
│   └── Plugin/
├── config/
│   └── install/
│       └── together_api.settings.yml
├── templates/
│   └── admin-test-page.html.twig
├── README.md
├── models_list.md
└── tests/
    └── src/
        ├── Unit/
        │   └── ClientTest.php
        └── Functional/
            └── TogetherApiTest.php
```

### c. Inne Rozważania Zgodne z Systemem Drupal

* **Użycie Pluginów (Opcjonalnie):** Dla rozszerzalności modułu.
* **Konfigurowalne Ustawienia:** Czas cache'owania, poziom logowania itp.
* **Integracja z Systemem Uprawnień:** Kontrola dostępu.
* **Dokumentacja Inline (PHPDoc).**
* **Automatyczne Testowanie (CI/CD).**
* **Obsługa Wielojęzyczności.**
* **Aktualizacje Bazy Danych (`hook_update_N()`).**
* **Zgodność z Drupal APIs (np. Entity API).**
* **Dobre Praktyki Bezpieczeństwa.**


## 6. Kroki Realizacji (skrócono)

1. Inicjalizacja Modułu
2. Implementacja Klasy Client
3. Konfiguracja Usługi
4. Implementacja Formularza Konfiguracyjnego
5. Tworzenie Podstrony dla Administratora
6. Implementacja Endpointów
7. Szablony Twig
8. Obsługa Błędów i Logowanie
9. Testowanie i Dokumentacja
10. Weryfikacja i Optymalizacja


## 7. Szczegółowe Wytyczne dla Podstrony Administratora

### a. Cel Podstrony

Umożliwienie testowania endpointów Together API.

### b. Funkcjonalności Podstrony

* **Formularze Testowe:**  Lista Modeli, Create Chat Completion, Create Completion, Create Image.
* **Wyświetlanie Wyników:** Czytelna prezentacja odpowiedzi (tekst, obraz).

### c. Implementacja Podstrony

* **Routing:** `/admin/config/together-api/test`
* **Kontroler:** `AdminTestController::testPage()`
* **Formularze:** `TestEndpointForm` (z metodami `buildForm()`, `validateForm()`, `submitForm()`).  Opcjonalnie AJAX.
* **Szablony:** `admin-test-page.html.twig`
* **Uprawnienia:** `administer together api`


### d. Przykładowy Kod Routing

```yaml
together_api.admin_test_page:
  path: '/admin/config/together-api/test'
  defaults:
    _controller: '\Drupal\together_api\Controller\AdminTestController::testPage'
    _title: 'Together API Test Page'
  requirements:
    _permission: 'administer together api'
```


This significantly improves readability and organization, making it easier to understand the module's purpose, functionality, and technical implementation details. I have also shortened repetitive sections and added clearer headings and lists. Remember to replace the placeholder links in section 3 with the actual URLs.
