# Система управления статусом кап (RUN/STOP/DELETE)

## Обзор

В систему анализа кап добавлена функциональность управления статусом кап с тремя возможными состояниями:
- **RUN** - активная капа (по умолчанию)
- **STOP** - остановленная капа
- **DELETE** - удаленная капа

## Новые команды

### Команда STOP (остановка капы)
```
Affiliate: G06
Recipient: TMedia
Cap: 20
Geo: AT
STOP
```

### Команда DELETE (удаление капы)
```
Affiliate: G06
Recipient: TMedia
Cap: 20
Geo: AT
DELETE
```

## Изменения в базе данных

### Таблица `caps`
- Добавлено поле `status` (enum: 'RUN', 'STOP', 'DELETE', по умолчанию 'RUN')
- Добавлено поле `status_updated_at` (timestamp)
- Добавлен индекс `[status, affiliate_name, recipient_name]`

### Таблица `caps_history`
- Добавлено поле `status` (enum: 'RUN', 'STOP', 'DELETE', по умолчанию 'RUN')
- Добавлено поле `status_updated_at` (timestamp)

## Поведение системы

### Создание новых кап
- Все новые капы автоматически получают статус `RUN`
- Поле `status_updated_at` устанавливается в текущее время

### Обновление существующих кап
- При обычном обновлении капы (изменение лимита, расписания и т.д.) статус не изменяется
- Поиск дубликатов происходит только среди кап со статусом `RUN` и `STOP` (исключая `DELETE`)

### Команды управления статусом
- **STOP**: изменяет статус на `STOP`, капа остается в системе но не отображается в обычном поиске
- **DELETE**: изменяет статус на `DELETE`, капа скрыта из всех поисков (кроме специального фильтра)
- При изменении статуса создается запись в истории
- Обновляется `status_updated_at` и `message_id`

### Поиск и фильтрация
- **По умолчанию**: показываются только активные капы (статус `RUN`)
- **Фильтр по статусу**: можно искать капы с любым статусом
- **Статистика**: учитываются только активные капы

## Изменения в коде

### Модель Cap
```php
// Новые поля в fillable
'status', 'status_updated_at'

// Новые методы
public function updateStatus($status)
public function scopeActive($query)
public function scopeNotDeleted($query)
public function isActive()
public function isStopped()
public function isDeleted()

// Обновлен метод findDuplicate
// Теперь ищет только среди RUN и STOP
```

### Модель CapHistory
```php
// Новые поля в fillable
'status', 'status_updated_at'

// Обновлен метод createFromCap
// Теперь сохраняет статус в историю
```

### CapAnalysisService
```php
// Новые методы
private function isStatusCommand($messageText)
private function processStatusCommand($messageId, $messageText)

// Обновлены методы поиска
public function searchCaps() // только активные по умолчанию
public function searchCapsWithFilters() // с фильтром по статусу
public function getFilterOptions() // добавлены опции статуса
```

## Команды для тестирования

### Запуск миграций
```bash
php artisan migrate
```

### Тестирование функциональности
```bash
php artisan test:cap-status-system
```

## Примеры использования

### 1. Создание капы
```
Affiliate: G06
Recipient: TMedia
Cap: 20
Geo: AT
Schedule: 24/7
```
**Результат**: Капа создана со статусом `RUN`

### 2. Остановка капы
```
Affiliate: G06
Recipient: TMedia
Cap: 20
Geo: AT
STOP
```
**Результат**: Капа получает статус `STOP`, создается запись в истории

### 3. Удаление капы
```
Affiliate: G06
Recipient: TMedia
Cap: 20
Geo: AT
DELETE
```
**Результат**: Капа получает статус `DELETE`, создается запись в истории

### 4. Поиск с фильтром
```php
// Только активные (по умолчанию)
$activeCaps = $capAnalysisService->searchCaps();

// Только остановленные
$stoppedCaps = $capAnalysisService->searchCapsWithFilters(null, null, ['status' => 'STOP']);

// Только удаленные
$deletedCaps = $capAnalysisService->searchCapsWithFilters(null, null, ['status' => 'DELETE']);

// Все кроме удаленных
$allCaps = $capAnalysisService->searchCapsWithFilters(null, null, ['status' => 'all']);
```

## Обработка ошибок

### Капа не найдена
```json
{
  "cap_entries_count": 0,
  "error": "Капа не найдена"
}
```

### Неверная команда
```json
{
  "cap_entries_count": 0,
  "error": "Неверная команда"
}
```

### Не все поля заполнены
```json
{
  "cap_entries_count": 0,
  "error": "Не все обязательные поля заполнены"
}
```

## Успешные ответы

### Изменение статуса
```json
{
  "cap_entries_count": 0,
  "updated_entries_count": 1,
  "status_changed": 1,
  "message": "Капа G06 → TMedia (AT, 20) остановлена"
}
```

## Особенности реализации

1. **Архивация истории**: При каждом изменении статуса создается запись в `caps_history`
2. **Поиск дубликатов**: Удаленные капы не участвуют в поиске дубликатов
3. **Фильтрация по умолчанию**: Все поисковые функции по умолчанию возвращают только активные капы
4. **Валидация**: Проверяется наличие всех обязательных полей (Affiliate, Recipient, Cap, Geo)
5. **Точное соответствие**: Поиск капы для изменения статуса происходит по точному совпадению всех полей

## Миграции

1. `2024_12_19_000010_add_status_to_caps_table.php` - добавляет поля статуса в таблицу caps
2. `2024_12_19_000011_add_status_to_caps_history_table.php` - добавляет поля статуса в таблицу caps_history

## Тестирование

Команда `php artisan test:cap-status-system` выполняет полный цикл тестирования:
1. Создание капы (проверка статуса RUN)
2. Остановка капы (проверка статуса STOP)
3. Удаление капы (проверка статуса DELETE)
4. Проверка фильтрации
5. Проверка истории изменений
6. Тестирование обработки ошибок

Система готова к использованию и полностью совместима с существующей функциональностью. 