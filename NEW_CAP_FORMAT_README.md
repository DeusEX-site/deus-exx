# Новый стандартный формат кап

## Что изменилось

Система кап была полностью переписана для работы с новым стандартным форматом сообщений. Теперь **система не ищет слово "cap" и синонимы**, а обрабатывает только структурированные сообщения.

## Новый формат сообщений

```
Affiliate: G06
Recipient: TMedia 
Cap: 15
Total: 
Geo: IE
Language: en
Funnel: 
Schedule: 10:00/18:00 GMT+03:00 
Date: 
Pending ACQ: No
Freeze status on ACQ: No
```

## Правила обработки

### Обязательные поля
- **Affiliate**: Название аффилейта (одно значение)
- **Recipient**: Название получателя (одно значение)
- **Cap**: Значение капы

### Разделение по гео
Система создает отдельную запись для каждого гео. Affiliate и Recipient содержат одно значение (без запятых):

- `Geo: GT PE MX` = 3 записи кап (по одной для каждого гео)
- `Geo: IE, DE, FR` = 3 записи кап (запятые и пробелы поддерживаются)
- `Geo: RU/UA/KZ` = 3 записи кап (слеши поддерживаются)
- `Geo: US` = 1 запись капы

### Логика значений по умолчанию

1. **Total**: 
   - Если пустое → бесконечность (-1)
   - Если в Cap есть слеш (например `Cap: 15/150`) → Total = бесконечность, Cap = первое число

2. **Date**: 
   - Если пустое → бесконечность (null)

3. **Schedule**: 
   - Если пустое → 24/7

4. **Pending ACQ / Freeze status**: 
   - Yes/True/1/Да → true
   - Остальное → false

## Добавленные поля в БД

- `language` (string) - Язык
- `funnel` (string) - Воронка  
- `pending_acq` (boolean) - Ожидание ACQ
- `freeze_status_on_acq` (boolean) - Заморозка статуса при ACQ
- `recipient_name` (переименовано из `broker_name`)

## Установка и обновление

### Автоматическое обновление
```bash
# Windows
update-to-new-cap-format.bat

# Linux/Mac
php artisan update:new-cap-format --test
```

### Ручное обновление
1. Применить миграцию:
```bash
php artisan migrate
```

2. Очистить старые записи кап (опционально):
```bash
php artisan db:seed --class=CleanOldCapsSeeder
```

## Примеры сообщений

### Пример 1: Полное сообщение
```
Affiliate: G06
Recipient: TMedia
Cap: 15
Total: 500
Geo: IE
Language: en
Funnel: Crypto
Schedule: 10:00/18:00 GMT+03:00
Date: 25.12.2024
Pending ACQ: No
Freeze status on ACQ: No
```

### Пример 2: Множественные гео
```
Affiliate: Webgate
Recipient: TradingM
Cap: 20
Total: 200
Geo: GT PE MX
Language: es
Funnel: Crypto
Schedule: 24/7
Date: 24.02
Pending ACQ: Yes
Freeze status on ACQ: No
```
Результат: Создается 3 записи кап (Webgate+TradingM для GT, PE, MX)

### Пример 3: Одно гео
```
Affiliate: TestAffiliate
Recipient: TestBroker
Cap: 30
Total: 
Geo: RU
Language: ru
Funnel: 
Schedule: 
Date: 
Pending ACQ: 
Freeze status on ACQ: 
```
Результат: Создается 1 запись капы (TestAffiliate+TestBroker для RU), с значениями по умолчанию

### Пример 4: Cap со слешем
```
Affiliate: Partner
Recipient: Broker
Cap: 20/200
Total: 100
Geo: US
Language: en
Funnel: 
Schedule: 
Date: 
Pending ACQ: 
Freeze status on ACQ: 
```
Результат: Cap=20, Total=бесконечность (слеш в Cap), Schedule=24/7

## Что НЕ обрабатывается

Система больше НЕ обрабатывает:
- Сообщения со словом "cap" в свободном формате
- Неструктурированные сообщения
- Сообщения без обязательных полей (Affiliate, Recipient, Cap)

## API изменения

### CapAnalysisService
- Убраны методы поиска cap по паттернам
- Добавлен `isStandardCapMessage()` 
- Добавлен `parseStandardCapMessage()`
- Обновлены все методы поиска и фильтрации

### Модель Cap
- Добавлены новые поля
- `broker_name` → `recipient_name`
- Обновлены cast'ы и fillable

### Представления (Views)
- Обновлено отображение "Брокер" → "Получатель"
- Добавлена поддержка новых полей в фильтрах
- Обновлен экспорт CSV

## Тестирование

Запуск тестов:
```bash
php artisan update:new-cap-format --test
```

Система протестирует:
- Парсинг стандартного формата
- Правильность значений по умолчанию
- Корректность обработки новых полей

## Миграция существующих данных

⚠️ **Внимание**: При обновлении все старые записи кап будут удалены, так как они не соответствуют новому формату.

Новые сообщения будут обрабатываться автоматически только если они соответствуют стандартному формату.

## Поддержка

При возникновении проблем:

1. Проверьте формат сообщения - он должен точно соответствовать стандарту
2. Убедитесь что поля Affiliate, Recipient, Cap заполнены
3. Запустите тесты: `php artisan update:new-cap-format --test`
4. Проверьте логи Laravel в `storage/logs/` 