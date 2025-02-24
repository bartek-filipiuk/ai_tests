# Ogólne
Pisz po polsku.
Uzywaj prostego i luźnego języka.

# Instrukcje dla Agenta AI w Projekcie Drupal

## 1. Konfiguracja Środowiska

### 1.1. Weryfikacja Konfiguracji
Przed rozpoczęciem pracy sprawdź:

1. **PHPStan**:
   ```bash
   ddev composer phpstan
   ```
   - Upewnij się, że poziom analizy jest ustawiony na 7 w `phpstan.neon`
   - Sprawdź czy wszystkie ścieżki są poprawnie skonfigurowane

2. **LSP i Indeksacja**:
   - Sprawdź czy `.vscode/settings.json` zawiera:
     - Konfigurację PHPStan
     - Ustawienia Intelephense
     - Asocjacje plików Drupal (*.module, *.install, itp.)

3. **Composer**:
   - Sprawdź czy wymagane pakiety są zainstalowane:
     - phpstan/phpstan
     - mglaman/phpstan-drupal
     - phpstan/phpstan-deprecation-rules

### 1.2. Narzędzia do Analizy
Dostępne narzędzia:
- `codebase_search`: Wyszukiwanie semantyczne w kodzie
- `grep_search`: Wyszukiwanie wzorców w plikach
- `view_file`: Przeglądanie zawartości plików
- `view_code_item`: Przeglądanie definicji klas/metod
- `edit_file`: Edycja plików
- `related_files`: Znajdowanie powiązanych plików

## 2. Flow Pracy

### 2.1. Przed Zmianami
1. **Analiza Kontekstu**:
   ```php
   // Przykład analizy serwisu
   - Sprawdź plik .services.yml
   - Znajdź wszystkie wstrzyknięcia zależności
   - Przeanalizuj interfejsy i klasy bazowe
   ```

2. **Mapowanie Zależności**:
   - Sprawdź pliki:
     - `*.services.yml` - definicje serwisów
     - `*.routing.yml` - kontrolery
     - `*.module` - hooki
     - `*.info.yml` - zależności modułów

3. **Analiza Użycia**:
   ```php
   // Przykład analizy metody
   - Znajdź wszystkie wywołania
   - Sprawdź implementacje interfejsów
   - Przeanalizuj hooki i eventy
   ```

### 2.2. Podczas Zmian
1. **Zachowanie Kompatybilności**:
   - Dodawaj nowe parametry z wartościami domyślnymi
   - Zachowuj istniejące sygnatury metod
   - Aktualizuj PHPDoc

2. **Analiza Wpływu**:
   - Sprawdź wpływ na:
     - Serwisy zależne
     - System pluginów
     - System zdarzeń
     - Szablony Twig

3. **Weryfikacja Typów**:
   ```php
   // Przykład weryfikacji typów
   - Użyj PHPStan do sprawdzenia typów
   - Sprawdź zgodność z interfejsami
   - Zweryfikuj typy w docblockach
   ```

### 2.3. Po Zmianach
1. **Weryfikacja**:
   ```bash
   # Uruchom PHPStan
   ddev composer phpstan
   
   # Sprawdź zależności
   ddev drush ev "print_r(array_keys(\Drupal::moduleHandler()->getImplementations('HOOK_NAME')));"
   ```

2. **Dokumentacja**:
   - Aktualizuj PHPDoc
   - Dodawaj komentarze do złożonej logiki
   - Dokumentuj zmiany w API

## 3. Wzorce i Konwencje

### 3.1. Struktura Modułów
```
module_name/
  ├── config/
  │   └── install/
  ├── src/
  │   ├── Controller/
  │   ├── Form/
  │   ├── Plugin/
  │   └── Service/
  ├── templates/
  ├── module_name.info.yml
  ├── module_name.module
  ├── module_name.routing.yml
  └── module_name.services.yml
```

### 3.2. Konwencje Nazewnicze
- **Klasy**: PascalCase
- **Metody**: camelCase
- **Hooki**: snake_case
- **Pliki konfiguracji**: snake_case

### 3.3. Dobre Praktyki
1. **Serwisy**:
   - Wstrzykuj zależności przez konstruktor
   - Używaj interfejsów zamiast konkretnych implementacji
   - Dokumentuj zależności w PHPDoc

2. **Kontrolery**:
   - Minimalizuj logikę biznesową
   - Używaj form API dla formularzy
   - Zwracaj tablice renderowalne

3. **Pluginy**:
   - Używaj adnotacji do konfiguracji
   - Implementuj odpowiednie interfejsy
   - Dokumentuj parametry pluginu

## 4. Debugowanie

### 4.1. Narzędzia
- PHPStan dla analizy statycznej
- Xdebug dla debugowania runtime
- Drush dla operacji na Drupalu
- Composer dla zarządzania zależnościami

### 4.2. Procedura Debugowania
1. **Analiza Statyczna**:
   ```bash
   ddev composer phpstan
   ```

2. **Logowanie**:
   ```php
   \Drupal::logger('module_name')->notice('Message');
   ```

3. **Stack Trace**:
   - Używaj try-catch do przechwytywania wyjątków
   - Loguj szczegóły błędów
   - Analizuj logi Drupala

## 5. Bezpieczeństwo

### 5.1. Walidacja Danych
- Zawsze waliduj dane wejściowe
- Używaj Form API do obsługi formularzy
- Escapuj dane wyjściowe w szablonach

### 5.2. Uprawnienia
- Sprawdzaj uprawnienia użytkowników
- Używaj systemu uprawnień Drupala
- Dokumentuj wymagane uprawnienia

## 6. Wydajność

### 6.1. Cache
- Używaj systemu cache Drupala
- Taguj cache odpowiednio
- Invaliduj cache tylko gdy potrzebne

### 6.2. Zapytania
- Używaj Entity Query
- Unikaj niepotrzebnych zapytań
- Wykorzystuj cache warstwy danych

## 7. SCSS i Style

### 7.1. Struktura SCSS
```scss
scss/
  ├── components/         # Komponenty wielokrotnego użytku
  ├── mixins/            # Mixiny SCSS
  ├── variables.scss     # Zmienne globalne
  ├── typography.scss    # Style typografii
  ├── import.scss        # Główny plik importów
  └── style.scss         # Główny plik stylów
```

### 7.2. Dobre Praktyki
1. **Organizacja Klas**:
   - Używaj BEM (Block Element Modifier)
   - Unikaj zagnieżdżania głębiej niż 3 poziomy
   - Grupuj style według komponentów

2. **Zmienne**:
   - Wszystkie zmienne globalne w `variables.scss`
   - Zmienne komponentów w ich własnych plikach
   - Używaj prefiksu dla zmiennych tematycznych (np. `$theme-color-primary`)

3. **Komponenty**:
   - Jeden komponent = jeden plik
   - Używaj prefiksu dla klas komponentów (np. `.oba-card`)
   - Dokumentuj zależności od innych komponentów

### 7.3. Narzędzia i Weryfikacja
1. **Stylelint**:
   ```json
   {
     "extends": "stylelint-config-standard-scss",
     "rules": {
       "selector-class-pattern": "^[a-z]([a-z0-9-]+)?(__([a-z0-9]+-?)+)?(--([a-z0-9]+-?)+){0,2}$",
       "max-nesting-depth": 3
     }
   }
   ```

2. **VS Code Extensions**:
   - SCSS IntelliSense
   - SCSS Formatter
   - StyleLint

3. **Gulpfile Tasks**:
   ```javascript
   // Sprawdzanie konfliktów klas
   gulp.task('scss-lint', () => {
     return gulp.src('scss/**/*.scss')
       .pipe(stylelint({
         reporters: [
           {formatter: 'string', console: true}
         ]
       }));
   });
   ```

### 7.4. Zapobieganie Konfliktom
1. **Audyt Klas**:
   - Regularnie sprawdzaj duplikaty klas
   - Używaj narzędzi do analizy używanych klas
   - Dokumentuj przestrzenie nazw komponentów

2. **Izolacja Komponentów**:
   ```scss
   // Dobra praktyka
   .oba-card {
     &__header { /* ... */ }
     &__content { /* ... */ }
     
     // Modyfikatory
     &--featured { /* ... */ }
   }
   ```

3. **Dokumentacja**:
   ```scss
   /// @group Components
   /// @name Card
   /// @description Podstawowy komponent karty
   /// @dependency variables.colors
   .oba-card { /* ... */ }
   ```

### 7.5. Workflow
1. **Przed Zmianami**:
   - Sprawdź istniejące style dla komponentu
   - Przeanalizuj zależności od zmiennych
   - Zweryfikuj użycie klas w szablonach

2. **Podczas Zmian**:
   - Używaj git blame do śledzenia historii zmian
   - Testuj style na różnych rozdzielczościach
   - Sprawdzaj konflikty z istniejącymi stylami

3. **Po Zmianach**:
   - Uruchom stylelint
   - Sprawdź optymalizację CSS
   - Zaktualizuj dokumentację komponentu
