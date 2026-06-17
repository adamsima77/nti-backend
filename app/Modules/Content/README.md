# Modul Content – Dokumentácia

> CMS systém pre správu obsahu: správy, stránky, partneri, FAQ, hero bannery, kontaktné formuláre.

---

## Obsah

1. [Prehľad modulu](#prehľad-modulu)
2. [Adresárová štruktúra](#adresárová-štruktúra)
3. [Kontroléry a logika](#kontroléry-a-logika)
4. [API Routes](#api-routes)
5. [Integrácie](#integrácie)
6. [Chybové stavy](#chybové-stavy)

---

## Prehľad modulu

Modul **Content** implementuje CMS (Content Management System) pre verejnú stránku platformy NTI:

- Správy (news/articles) s kategóriami
- Statické stránky (about, FAQ, ...)
- Partneri a hero bannery
- Kontaktný formulár
- Štatistiky pre CMS

Obsah je dostupný verejne (bez autentifikácie). Správa obsahu je chránená rolou `cms_editor` alebo `nti_admin`.

---

## Adresárová štruktúra

```
Modules/Content/
├── app/
│   ├── Http/
│   │   └── Controllers/
│   │       ├── ContentController.php       # Správy, stránky, partneri, FAQ, bannery
│   │       ├── ContactController.php       # Kontaktný formulár
│   │       └── CmsStatsController.php      # Štatistiky pre CMS
│   ├── Models/
│   │   ├── Article.php
│   │   ├── Page.php
│   │   ├── Partner.php
│   │   ├── Faq.php
│   │   ├── HeroBanner.php
│   │   └── ContactSubmission.php
│   ├── Policies/
│   │   └── ContentPolicy.php
│   └── Providers/
│       └── ContentServiceProvider.php
├── database/
│   ├── migrations/
│   └── seeders/
└── routes/
    └── api.php
```

---

## Kontroléry a logika

### ContentController

Hlavný kontrolér pre správu obsahu.

**Typy obsahu:**
- `Article` – správy/články
- `Page` – statické stránky
- `Partner` – partneri
- `Faq` – FAQ záznamy
- `HeroBanner` – hero bannery na titulnej stránke

### ContactController

Spracúva kontaktné formuláre odoslané z verejnej stránky.

### CmsStatsController

Štatistiky pre CMS dashboard (počty článkov, stránok, ...).

---

## API Routes

**Súbor:** `routes/api.php`

### Verejné endpointy (throttle skupina `public-content`)

Tieto endpointy sú prístupné bez autentifikácie. Throttle limit obmedzuje frekveniu čítania verejného obsahu.

| Metóda | URL | Popis |
|--------|-----|-------|
| `GET` | `/api/content/articles` | Zoznam článkov |
| `GET` | `/api/content/articles/{article}` | Detail článku |
| `GET` | `/api/content/pages` | Zoznam stránok |
| `GET` | `/api/content/pages/{page}` | Detail stránky |
| `GET` | `/api/content/partners` | Zoznam partnerov |
| `GET` | `/api/content/faq` | FAQ záznamy |
| `GET` | `/api/content/hero-banners` | Hero bannery |

### Kontaktný formulár (throttle skupina `contact`)

| Metóda | URL | Popis |
|--------|-----|-------|
| `POST` | `/api/contact` | Odoslanie kontaktného formulára |

### Admin / CMS (auth:sanctum + verified + cms_editor/nti_admin)

#### Správa obsahu

| Metóda | URL | Popis |
|--------|-----|-------|
| `POST` | `/api/cms/articles` | Vytvorenie článku |
| `PUT` | `/api/cms/articles/{article}` | Aktualizácia článku |
| `DELETE` | `/api/cms/articles/{article}` | Zmazanie článku |
| `POST` | `/api/cms/pages` | Vytvorenie stránky |
| `PUT` | `/api/cms/pages/{page}` | Aktualizácia stránky |
| `DELETE` | `/api/cms/pages/{page}` | Zmazanie stránky |
| `POST` | `/api/cms/partners` | Vytvorenie partnera |
| `PUT` | `/api/cms/partners/{partner}` | Aktualizácia partnera |
| `DELETE` | `/api/cms/partners/{partner}` | Zmazanie partnera |
| `POST` | `/api/cms/faq` | Vytvorenie FAQ |
| `PUT` | `/api/cms/faq/{faq}` | Aktualizácia FAQ |
| `DELETE` | `/api/cms/faq/{faq}` | Zmazanie FAQ |
| `POST` | `/api/cms/hero-banners` | Vytvorenie bannera |
| `PUT` | `/api/cms/hero-banners/{banner}` | Aktualizácia bannera |
| `DELETE` | `/api/cms/hero-banners/{banner}` | Zmazanie bannera |

#### CMS Štatistiky

| Metóda | URL | Popis |
|--------|-----|-------|
| `GET` | `/api/cms/stats` | CMS štatistiky |

---

## Integrácie

### IdentityAccess
- Prístup k CMS zápisom je obmedzený rolami `cms_editor` a `nti_admin`
- `$user->isCMSEditor()` – kontrola roly `cms_editor`

### Throttle skupiny
- `public-content` – throttle pre verejné čítanie obsahu
- `contact` – throttle pre kontaktný formulár (ochrana pred spam-om)

---

## Chybové stavy

| HTTP Kód | Situácia |
|----------|----------|
| `200` | Obsah načítaný |
| `201` | Obsah vytvorený |
| `403` | Nedostatočné oprávnenia (nie cms_editor/admin) |
| `404` | Obsah nenájdený |
| `422` | Validačná chyba kontaktného formulára |
| `429` | Príliš veľa požiadaviek (throttle) |

---

*Modul Content – NTI Backend | Laravel 12*
