# AutoblogerAI Module Documentation

## Overview
AutoblogerAI to moduł Drupal służący do automatycznego generowania wpisów blogowych przy użyciu sztucznej inteligencji. Moduł integruje się z systemem Drupal i oferuje interfejs administracyjny do zarządzania generowaniem treści.

## Główne Funkcje
- Automatyczne generowanie wpisów blogowych
- Konfiguracja tematów i parametrów generowania
- System kolejkowania zadań generowania treści
- Interfejs administracyjny do zarządzania procesem

## Struktura Modułu

### Pliki Konfiguracyjne
- `autobloger_ai.info.yml` - Podstawowa konfiguracja modułu
- `autobloger_ai.routing.yml` - Definicje ścieżek URL
- `autobloger_ai.services.yml` - Definicje serwisów
- `autobloger_ai.module` - Główny plik modułu

### Formularze
Moduł zawiera trzy główne formularze dostępne w panelu administracyjnym:
1. Formularz Ustawień (`/admin/config/content/autobloger-ai`)
2. Formularz Generowania Postów (`/admin/content/autobloger-ai/generate`)
3. Konfiguracja Tematów Bloga (`/admin/config/content/autobloger-ai/subjects`)

### Serwisy
Moduł wykorzystuje dwa główne serwisy:
1. `AutoblogerAiService` - Główny serwis odpowiedzialny za generowanie treści
2. `BlogPostQueueService` - Serwis zarządzający kolejką zadań generowania

## Uprawnienia
Do korzystania z modułu wymagane są uprawnienia administratora (`administer site configuration`).

## Zależności
- Moduł wymaga Drupala w wersji 10 lub 11
- Zależność od modułu AI (`ai:ai`)

## Instalacja i Konfiguracja
1. Zainstaluj moduł przez panel administracyjny Drupala
2. Skonfiguruj podstawowe ustawienia w `/admin/config/content/autobloger-ai`
3. Skonfiguruj tematy blogowe w `/admin/config/content/autobloger-ai/subjects`
4. Rozpocznij generowanie treści przez `/admin/content/autobloger-ai/generate`
