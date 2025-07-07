# Логика привязки капы по порядку (Sequence-based Parsing)

## Обзор изменений

Система теперь поддерживает привязку полей капы по порядку написания вместо создания всех возможных комбинаций.

## Принцип работы

### Обязательные поля (должны присутствовать)
- `Affiliate` - один аффилейт для всех записей
- `Recipient` - один получатель для всех записей  
- `Cap` - список значений кап (определяет количество записей)
- `Geo` - список гео (должен совпадать по количеству с Cap)

### Необязательные поля (с привязкой по порядку)
- `Language` - языки по порядку (по умолчанию: "en")
- `Funnel` - воронки по порядку (по умолчанию: null)
- `Total` - лимиты по порядку (по умолчанию: -1, бесконечность)
- `Schedule` - расписания по порядку (по умолчанию: "24/7")
- `Date` - даты по порядку (по умолчанию: null, бесконечность)  
- `Pending ACQ` - статусы по порядку (по умолчанию: false)
- `Freeze status on ACQ` - статусы по порядку (по умолчанию: false)

## Разделители полей

### Пробелы и запятые (Cap, Geo, Language, Total):
```
Cap: 15 25 30
Geo: IE DE ES  
Language: en de es
Total: 120 150 200
```

### Только запятые (Funnel, Schedule, Date, Pending ACQ, Freeze status):
```
Funnel: Funnel1, Funnel2, Funnel3
Schedule: 10:00/18:00 GMT+03:00, 12:00/20:00 GMT+03:00, 24/7
Date: 24.02, 25.02, 26.02
Pending ACQ: Yes, No, Yes
Freeze status on ACQ: No, Yes, No
```

## Парсинг времени

Система распознает формат времени `HH:MM/HH:MM GMT±HH:MM` и разбивает на отдельные поля:

### Входной формат:
```
Schedule: 18:00/01:00 GMT+03:00
```

### Результат:
- `schedule`: "18:00/01:00 GMT+03:00"
- `start_time`: "18:00"
- `end_time`: "01:00"  
- `timezone`: "GMT+03:00"
- `is_24_7`: false

### Специальные значения:
- `24/7` или `24-7` → is_24_7 = true, start_time/end_time = null
- Пустое значение → 24/7 по умолчанию

## Примеры

### Пример 1: Полное сообщение
```
Affiliate: MultiTest
Recipient: TestMulti
Cap: 15 25 30
Geo: IE DE ES
Language: en de es
Funnel: Funnel1, Funnel2, Funnel3
Total: 120 150 200
Schedule: 10:00/18:00 GMT+03:00, 12:00/20:00 GMT+03:00, 24/7
Date: 24.02, 25.02, 26.02
Pending ACQ: Yes, No, Yes
Freeze status on ACQ: No, Yes, No
```

**Результат - 3 записи:**
1. Cap=15, Geo=IE, Language=en, Funnel=Funnel1, Total=120, Schedule=10:00/18:00 GMT+03:00, Date=24.02.2024, Pending ACQ=Yes, Freeze=No
2. Cap=25, Geo=DE, Language=de, Funnel=Funnel2, Total=150, Schedule=12:00/20:00 GMT+03:00, Date=25.02.2024, Pending ACQ=No, Freeze=Yes  
3. Cap=30, Geo=ES, Language=es, Funnel=Funnel3, Total=200, Schedule=24/7, Date=26.02.2024, Pending ACQ=Yes, Freeze=No

### Пример 2: Недостающие значения (заполнение по умолчанию)
```
Affiliate: DefaultTest
Recipient: TestDefault
Cap: 10 20
Geo: US UK
Language: fr
Total: 100
```

**Результат - 2 записи:**
1. Cap=10, Geo=US, Language=fr, Total=100, Schedule=24/7, Date=null, Funnel=null, Pending ACQ=false, Freeze=false
2. Cap=20, Geo=UK, Language="en" (по умолчанию), Total=-1 (бесконечность), Schedule=24/7, Date=null, Funnel=null, Pending ACQ=false, Freeze=false

### Пример 3: Ошибка - неравное количество Cap и Geo
```
Affiliate: MismatchTest
Recipient: TestMismatch
Cap: 15 25 30
Geo: IE DE
```

**Результат:** Отклонено (Cap.length ≠ Geo.length)

## Добавленные поля в БД

В таблицу `caps` добавлены новые поля:
- `start_time` (nullable) - время начала работы
- `end_time` (nullable) - время окончания работы  
- `timezone` (nullable) - часовой пояс

## Изменения в API

### CSV Экспорт
Добавлены колонки:
- "Время начала"
- "Время окончания"  
- "Часовой пояс"

### JSON Response
Все API ответы теперь включают:
```json
{
  "analysis": {
    "start_time": "18:00",
    "end_time": "01:00", 
    "timezone": "GMT+03:00",
    ...
  }
}
```

## Файлы изменены

- `app/Services/CapAnalysisService.php` - полная переработка логики парсинга
- `app/Models/Cap.php` - добавлены новые поля в fillable
- `database/migrations/2024_12_19_000008_add_time_fields_to_caps_table.php` - миграция
- `resources/views/cap-analysis.blade.php` - обновлен CSV экспорт
- `app/Console/Commands/UpdateToNewCapFormat.php` - новые тесты

## Миграция

Для применения изменений выполните:
```bash
php artisan migrate
```

## Тестирование

Запустите тестовую команду:
```bash
php artisan cap:update-to-new-format
```

Команда включает 10 тестов, включая:
- Множественные капы с полной привязкой
- Заполнение значений по умолчанию
- Парсинг времени
- Валидацию равенства количества Cap и Geo 