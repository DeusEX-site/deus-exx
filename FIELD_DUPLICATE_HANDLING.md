# Обработка дублирующихся и пустых полей

## Проблема
При получении сообщений из Telegram API могут присутствовать дублирующиеся поля (одно с данными, другие пустые). Это касается всех необязательных полей:
- Schedule
- Date
- Language
- Funnel  
- Total
- Pending ACQ
- Freeze status on ACQ

## Реализованное решение
Обновлена логика парсинга всех полей в `CapAnalysisService.php` для обработки дублирующихся значений. Изменения применены в двух методах:
- `parseStandardCapMessage` - для создания новых кап
- `processCapUpdate` - для обновления существующих кап

## Логика работы
1. Используем `preg_match_all` вместо `preg_match` для поиска всех вхождений поля
2. Проходим по всем найденным значениям
3. Берем первое непустое значение  
4. Если все значения пустые, используем значение по умолчанию

## Значения по умолчанию для пустых полей
- **Language**: 'en'
- **Funnel**: null
- **Total**: -1 (бесконечность)
- **Schedule**: '24/7' 
- **Date**: null (бесконечность)
- **Pending ACQ**: false
- **Freeze status on ACQ**: false

## Пример реализации

### Language
```php
// Обработка Language с учетом возможных дубликатов и пустых значений
if (preg_match_all('/^Language:\s*(.*)$/m', $messageText, $matches)) {
    $languageValue = null;
    foreach ($matches[1] as $match) {
        $trimmed = trim($match);
        if (!$this->isEmpty($trimmed)) {
            $languageValue = $trimmed;
            break;
        }
    }
    
    if (!$this->isEmpty($languageValue)) {
        $languages = $this->parseMultipleValues($languageValue);
    } else {
        $languages = ['en']; // Значение по умолчанию для пустого поля
    }
}
```

### Total
```php
// Обработка Total с учетом возможных дубликатов и пустых значений
if (preg_match_all('/^Total:\s*(.*)$/m', $messageText, $matches)) {
    $totalValue = null;
    foreach ($matches[1] as $match) {
        $trimmed = trim($match);
        if (!$this->isEmpty($trimmed)) {
            $totalValue = $trimmed;
            break;
        }
    }
    
    if (!$this->isEmpty($totalValue)) {
        $totalValues = $this->parseMultipleValues($totalValue);
        foreach ($totalValues as $total) {
            if (is_numeric($total)) {
                $totals[] = intval($total);
            } else {
                $totals[] = -1; // Бесконечность для нечисловых значений
            }
        }
    } else {
        $totals = [-1]; // Значение по умолчанию для пустого поля
    }
}
```

## Тестирование
Для проверки обработки пустых полей используйте:
```bash
php test_empty_fields.php
```

Для проверки парсинга расписания:
```bash
php test_schedule_parsing.php
```

## Правила обработки
1. **Приоритет**: Всегда выбирается первое непустое значение
2. **Пустые значения**: Пустые строки или только пробелы игнорируются
3. **Метод isEmpty()**: Проверяет на пустоту с учетом "-" и "0" как валидных значений
4. **Множественные значения**: После выбора непустого значения, оно может содержать несколько значений, разделенных запятыми или пробелами 