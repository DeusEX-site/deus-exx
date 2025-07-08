# Исправление парсинга пустых полей

## Проблема
При использовании регулярного выражения `(.*)$` с модификатором `m`, оно могло захватывать следующую строку вместо пустого значения.

Например, для строки:
```
Language:
Funnel:
```

Регулярное выражение `/^Language:\s*(.*)$/m` захватывало `Funnel:` вместо пустой строки.

## Решение
Заменили `(.*)` на `([^\n\r]*)` во всех регулярных выражениях для парсинга полей.

Это означает "захватить все символы кроме символов новой строки".

## Изменения
Обновлены регулярные выражения для всех полей в двух методах:
- `parseStandardCapMessage` - для создания новых кап
- `processCapUpdate` - для обновления кап через reply

### До:
```php
if (preg_match_all('/^Language:\s*(.*)$/m', $messageText, $matches)) {
```

### После:
```php
if (preg_match_all('/^Language:\s*([^\n\r]*)$/m', $messageText, $matches)) {
```

## Затронутые поля
- Language
- Funnel
- Total
- Schedule
- Date
- Pending ACQ
- Freeze status on ACQ

## Результат
Теперь пустые поля правильно распознаются как пустые и используются значения по умолчанию:
- Language: 'en'
- Funnel: null
- Total: -1 (бесконечность)
- Schedule: '24/7'
- Date: null
- Pending ACQ: false
- Freeze status on ACQ: false

## Тестирование
Для проверки используйте:
```bash
php test_empty_fields_debug.php
``` 