# Обновление логики обработки пустых полей

## Проблема
Система не корректно обрабатывала пустые поля в сообщениях. Поля могут быть:
- Отсутствующими
- Пустыми (только пробелы)
- Содержать символ "-" (что означает отсутствие значения)

## Решение
Добавлена функция `isEmpty()` для проверки пустых значений и обновлена логика всех полей.

### Функция isEmpty()
```php
private function isEmpty($value)
{
    if ($value === null) return true;
    $trimmed = trim($value);
    return $trimmed === '' || $trimmed === '-';
}
```

### Правила обработки пустых полей

1. **Total**: 
   - Пустое/"-" → бесконечность (-1)
   - Число → это число

2. **Schedule**: 
   - Пустое/"-" → 24/7 (по умолчанию)
   - Значение → это значение

3. **Date**: 
   - Пустое/"-" → бесконечность (null)
   - Значение → это значение (с добавлением года если нужно)

4. **Language**: 
   - Пустое/"-" → "en" (по умолчанию)
   - Значение → это значение

5. **Funnel**: 
   - Пустое/"-" → null
   - Значение → это значение

6. **Pending ACQ / Freeze status**: 
   - Пустое/"-" → false
   - Yes/True/1/Да → true
   - Остальное → false

7. **Geo**: 
   - Пустое/"-" → игнорируется
   - Значение → парсится и разделяется

8. **Affiliate / Recipient**: 
   - Пустое/"-" → игнорируется (обязательные поля)
   - Значение → это значение

## Примеры

### Пример 1: Полностью пустые опциональные поля
```
Affiliate: TestAff
Recipient: TestRec
Cap: 15
Total: 
Geo: US
Language: 
Funnel: 
Schedule: 
Date: 
Pending ACQ: 
Freeze status on ACQ: 
```
Результат: Total=бесконечность, Language="en", Schedule=24/7, Date=бесконечность, Funnel=null, Pending ACQ=false, Freeze=false

### Пример 2: Поля со значением "-"
```
Affiliate: TestAff
Recipient: TestRec
Cap: 10
Total: -
Geo: DE
Language: -
Funnel: -
Schedule: -
Date: -
Pending ACQ: -
Freeze status on ACQ: -
```
Результат: Total=бесконечность, Language="en", Schedule=24/7, Date=бесконечность, Funnel=null, Pending ACQ=false, Freeze=false

## Файлы изменены
- `app/Services/CapAnalysisService.php` - добавлена функция isEmpty() и обновлена логика всех полей
- `app/Console/Commands/UpdateToNewCapFormat.php` - добавлен тест с пустыми полями
- `NEW_CAP_FORMAT_README.md` - обновлена документация с новыми правилами

## Тестирование
Добавлен тест в команде `UpdateToNewCapFormat` для проверки корректной обработки пустых полей и значений "-". 