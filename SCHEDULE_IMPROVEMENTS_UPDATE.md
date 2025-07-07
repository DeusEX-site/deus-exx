# Улучшения парсинга Schedule и логики заполнения

## Изменения

### 1. Schedule теперь поддерживает пробелы и запятые
**Было:** только запятые
```
Schedule: 10:00/18:00, 12:00/20:00, 24/7
```

**Стало:** пробелы и запятые
```
Schedule: 10:00/18:00 12:00/20:00 GMT+03:00 24/7
```

### 2. GMT убран из поля Schedule
**Было:**
```
Schedule: "18:00/01:00 GMT+03:00"
```

**Стало:**
```
Schedule: "18:00/01:00"  // Только время
start_time: "18:00"
end_time: "01:00"
timezone: "GMT+03:00"   // GMT отдельно
```

### 3. Одно значение применяется ко всем записям
**Новая логика:** Если у необязательного поля только одно значение, оно применяется ко всем записям.

**Пример:**
```
Affiliate: G06
Recipient: TMedia
Cap: 15 20
Geo: DE AT
Language: de
Funnel: DeusEX
Total: 200
```

**Результат:**
- Запись 1: Cap=15, Geo=DE, Language=de, Funnel=DeusEX, Total=200
- Запись 2: Cap=20, Geo=AT, Language=de, Funnel=DeusEX, Total=200

## Обновленные разделители

### Пробелы и запятые:
- Cap
- Geo  
- Language
- Total
- **Schedule** (новое)

### Только запятые:
- Funnel
- Date
- Pending ACQ
- Freeze status

## Логика заполнения

1. **Одно значение** → применяется ко всем записям
2. **Меньше значений чем записей** → дополняется по умолчанию:
   - Language: "en"
   - Funnel: null
   - Total: -1 (бесконечность)
   - Schedule: "24/7"
   - Date: null
   - Pending ACQ: false
   - Freeze status: false

## Примеры

### Пример 1: Schedule через пробелы
```
Affiliate: SpaceTest
Recipient: TestSpace
Cap: 10 15 20
Geo: FR IT ES
Schedule: 10:00/18:00 12:00/20:00 GMT+03:00 24/7
Language: fr it es
```

**Результат:**
1. Schedule="10:00/18:00", start_time="10:00", end_time="18:00", timezone=null
2. Schedule="12:00/20:00", start_time="12:00", end_time="20:00", timezone="GMT+03:00"  
3. Schedule="24/7", is_24_7=true

### Пример 2: Общие значения
```
Affiliate: G06
Recipient: TMedia
Cap: 15 20
Geo: DE AT
Language: de
Funnel: DeusEX
Schedule: 18:00/01:00 GMT+03:00
```

**Результат:** Оба записи получают одинаковые Language, Funnel, Schedule

## Изменения в коде

### CapAnalysisService.php
1. `parseMultipleValues()` для Schedule без `$separateByCommaOnly`
2. `parseScheduleTime()` - GMT убирается из schedule
3. Логика `array_fill()` для одиночных значений

### Файлы обновлены:
- `app/Services/CapAnalysisService.php`
- `app/Console/Commands/UpdateToNewCapFormat.php` (новые тесты)
- `SEQUENCE_BASED_CAP_PARSING.md` (обновлена документация)

## Тестирование

Добавлены тесты:
- Тест 11: Одно значение применяется ко всем
- Тест 12: Schedule через пробелы

Запуск:
```bash
php artisan cap:update-to-new-format
``` 