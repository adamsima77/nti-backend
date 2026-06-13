# Modul Students – Dokumentácia

> Správa študentských profilov, akademických záznamov a škôl na platforme NTI.

---

## Obsah

1. [Prehľad modulu](#prehľad-modulu)
2. [Adresárová štruktúra](#adresárová-štruktúra)
3. [Modely a databázová schéma](#modely-a-databázová-schéma)
4. [Kontroléry a logika](#kontroléry-a-logika)
5. [API Routes](#api-routes)
6. [Integrácie](#integrácie)
7. [Chybové stavy](#chybové-stavy)

---

## Prehľad modulu

Modul **Students** spravuje profily študentov registrovaných na platforme NTI. Zahŕňa:

- Profil študenta s prepojením na univerzitnú štruktúru
- Akademické záznamy (AcademicRecord)
- Akademické vlajky (AcademicFlag) – špeciálne označenia
- Číselníky: university, study_program, study_field, study_year
- Verejné číselníky podľa jazyka (bez autentifikácie)
- Dashboard pre študentov

---

## Adresárová štruktúra

```
Modules/Students/
├── app/
│   ├── Http/
│   │   └── Controllers/
│   │       ├── StudentController.php
│   │       ├── UniversityController.php
│   │       ├── StudyProgramController.php
│   │       ├── StudyFieldController.php
│   │       └── StudyYearController.php
│   ├── Models/
│   │   ├── Student.php
│   │   ├── AcademicRecord.php
│   │   ├── AcademicFlag.php
│   │   ├── University.php
│   │   ├── StudyProgram.php
│   │   ├── StudyField.php
│   │   └── StudyYear.php
│   ├── Policies/
│   │   └── StudentPolicy.php
│   └── Providers/
│       └── StudentsServiceProvider.php
├── database/
│   ├── migrations/
│   └── seeders/
└── routes/
    └── api.php
```

---

## Modely a databázová schéma

### Student

**Tabuľka:** `student`

```
student
├── id
├── user_id           (FK → users.id)
├── study_program_id  (FK → study_program.id)
├── study_field_id    (FK → study_field.id)
├── university_id     (FK → university.id)
├── cv_document_id    (FK → document.id, nullable)
├── study_year_id     (FK → study_year.id)
├── portfolio_url     (string, nullable)
└── timestamps
```

**Model:**

```php
class Student extends Model
{
    protected $table = 'student';

    protected $fillable = [
        'user_id',
        'study_program_id',
        'study_field_id',
        'university_id',
        'cv_document_id',
        'study_year_id',
        'portfolio_url',
    ];
}
```

**Relácie:**

| Metóda | Typ | Cieľ |
|--------|-----|------|
| `user()` | `BelongsTo` | `User` |
| `studyProgram()` | `BelongsTo` | `StudyProgram` |
| `studyField()` | `BelongsTo` | `StudyField` |
| `university()` | `BelongsTo` | `University` |
| `studyYear()` | `BelongsTo` | `StudyYear` |
| `cvDocument()` | `BelongsTo` | `Document` |
| `academicRecord()` | `HasOne` | `AcademicRecord` |
| `academicFlags()` | `BelongsToMany` | `AcademicFlag` cez `student_has_academic_flags` |

### Pivot `student_has_academic_flags`

```
student_has_academic_flags
├── student_id         (FK → student.id)
└── academic_flags_id  (FK → academic_flag.id)
```

> **Dôležité:** FK stĺpec v pivot tabuľke sa volá `academic_flags_id` (nie `academic_flag_id`).

---

### AcademicRecord

**Tabuľka:** `academic_record`

Obsahuje akademický záznam študenta (prospech, ročník, certifikáty).

**Relácia:** `Student.academicRecord()` → `HasOne(AcademicRecord)`

---

### AcademicFlag

**Tabuľka:** `academic_flag`

Špeciálne označenia, ktoré môžu byť priradené študentom. Tieto vlajky sú relevantné pri výpočte atribútu `academic_flag` na modeli `Application`.

---

### University

**Tabuľka:** `university`

Číselník univerzít. Každý záznam má preložené názvy podľa jazyka.

---

### StudyProgram

**Tabuľka:** `study_program`

Číselník študijných programov. Záznamy sú filtrovateľné podľa jazyka.

---

### StudyField

**Tabuľka:** `study_field`

Číselník odborov štúdia. Záznamy sú filtrovateľné podľa jazyka.

---

### StudyYear

**Tabuľka:** `study_year`

Číselník ročníkov štúdia. Záznamy sú filtrovateľné podľa jazyka.

---

## Kontroléry a logika

### StudentController

| Metóda | Popis |
|--------|-------|
| `index()` | Zoznam študentov (admin) |
| `show($id)` | Detail študenta |
| `store()` | Vytvorenie profilu študenta |
| `update($id)` | Aktualizácia profilu |
| `destroy($id)` | Zmazanie profilu |
| `me()` | Profil aktuálneho prihláseneho študenta |
| `dashboard()` | Dashboard pre študenta |
| `getAcademicRecord()` | Akademický záznam aktuálneho študenta |
| `updateUniversity($id)` | Aktualizácia univerzity |
| `updateAcademicFlag($id)` | Aktualizácia akademickej vlajky |
| `updateStudyField($id)` | Aktualizácia odboru |
| `updateStudyProgram($id)` | Aktualizácia programu |
| `updateStudyYear($id)` | Aktualizácia ročníka |

---

## API Routes

**Súbor:** `routes/api.php`

### Verejné číselníky (bez autentifikácie)

Tieto endpointy sú verejné a filtrovateľné podľa jazyka (`?lang=sk` alebo `?lang=en`):

| Metóda | URL | Popis |
|--------|-----|-------|
| `GET` | `/api/study-fields` | Zoznam odborov |
| `GET` | `/api/study-years` | Zoznam ročníkov |
| `GET` | `/api/study-programs` | Zoznam programov |

### Chránené endpointy (auth:sanctum + verified)

#### Správa študentov

| Metóda | URL | Popis |
|--------|-----|-------|
| `GET` | `/api/students` | Zoznam študentov (admin) |
| `GET` | `/api/students/{student}` | Detail študenta |
| `POST` | `/api/students` | Vytvorenie profilu |
| `PUT` | `/api/students/{student}` | Aktualizácia |
| `DELETE` | `/api/students/{student}` | Zmazanie |
| `GET` | `/api/students/me` | Môj profil |

#### Aktualizácie čiastočných atribútov

| Metóda | URL | Popis |
|--------|-----|-------|
| `PATCH` | `/api/students/{student}/university` | Aktualizácia univerzity |
| `PATCH` | `/api/students/{student}/academic-flag` | Aktualizácia vlajky |
| `PATCH` | `/api/students/{student}/study-field` | Aktualizácia odboru |
| `PATCH` | `/api/students/{student}/study-program` | Aktualizácia programu |
| `PATCH` | `/api/students/{student}/study-year` | Aktualizácia ročníka |

#### Akademický záznam a dashboard

| Metóda | URL | Popis |
|--------|-----|-------|
| `GET` | `/api/student/academic-record` | Akademický záznam |
| `GET` | `/api/v1/student/dashboard` | Dashboard pre študenta |

#### Číselníky (auth)

| Metóda | URL | Popis |
|--------|-----|-------|
| `GET` | `/api/universities` | Zoznam univerzít |
| `GET` | `/api/study-fields` | Odbory (filtrovateľné) |
| `GET` | `/api/study-programs` | Programy (filtrovateľné) |
| `GET` | `/api/study-years` | Ročníky (filtrovateľné) |

---

## Integrácie

### IdentityAccess
- `Student.user_id` → `users.id`
- `User.student()` → `HasOne(Student)`
- Profil študenta sa vytvára pri onboardingu (POST /student-onboarding)

### Applications
- `Application.academic_flag` – computed atribút kontroluje `academicFlags` všetkých členov tímu
- Akademické vlajky ovplyvňujú výsledok `is_academic_signal` kritérií pri hodnotení

### Teams
- Členovia tímu môžu byť študenti (model User s rolou `student`)
- `TeamsController.formatTeamForStudent()` zobrazuje info tímu pre dashboard študenta

---

## Chybové stavy

| HTTP Kód | Situácia |
|----------|----------|
| `200` | Profil načítaný/aktualizovaný |
| `201` | Profil vytvorený |
| `403` | Nedostatočné oprávnenia |
| `404` | Profil nenájdený |
| `422` | Validačná chyba |

---

*Modul Students – NTI Backend | Laravel 12*
