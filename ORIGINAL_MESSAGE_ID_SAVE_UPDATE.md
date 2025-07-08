# Сохранение original_message_id в caps и caps_history

## Обзор

Добавлено сохранение поля `original_message_id` в таблицы `caps` и `caps_history` для правильного отслеживания оригинального сообщения при создании и обновлении кап.

## Проблема

Ранее поле `original_message_id` было добавлено в структуру базы данных и модели, но не заполнялось при создании и обновлении кап. это приводило к тому, что:

1. При создании новых кап `original_message_id` оставался `null`
2. При обновлении кап через reply сообщения `original_message_id` не устанавливался
3. В истории кап `original_message_id` также оставался `null`

## Решение

### 1. Обновлен CapAnalysisService

**Файл:** `app/Services/CapAnalysisService.php`

#### Создание новых кап (метод `analyzeAndSaveCapMessage`)

```php
// Дубликат не найден - создаем новую запись
Cap::create([
    'message_id' => $messageId,
    'original_message_id' => $messageId, // Для новых кап original_message_id равен message_id
    'cap_amounts' => [$capData['cap_amount']],
    // ... остальные поля
]);
```

#### Создание кап при обновлении (метод `processCapUpdate`)

```php
// Капа для этого гео не найдена - создаем новую на основе исходной капы
$newCapData = [
    'message_id' => $originalCap->message_id,
    'original_message_id' => $originalCap->original_message_id ?: $originalCap->message_id, // Сохраняем original_message_id
    'cap_amounts' => [isset($caps[$i]) ? $caps[$i] : $originalCap->cap_amounts[0]],
    // ... остальные поля
];
```

### 2. Создана команда для заполнения существующих записей

**Файл:** `app/Console/Commands/PopulateOriginalMessageId.php`

Команда для заполнения missing `original_message_id` значений в существующих записях:

```bash
php artisan populate:original-message-id
```

### 3. Создан batch-файл для удобного запуска

**Файл:** `populate-original-message-id.bat`

```batch
@echo off
echo 🔄 Populating missing original_message_id values...
php artisan populate:original-message-id
echo 🎉 Все записи обновлены!
pause
```

### 4. Создан тест для проверки

**Файл:** `test_original_message_id.php`

Тест проверяет:
- Создание новых кап с правильным `original_message_id`
- Обновление кап через reply сообщения
- Сохранение `original_message_id` в истории кап
- Создание новых кап для новых гео с наследованием `original_message_id`

**Файл:** `test-original-message-id.bat`

```batch
@echo off
echo 🧪 Тестирование сохранения original_message_id...
php test_original_message_id.php
pause
```

## Логика работы

### Для новых кап
- При создании новой капы `original_message_id` устанавливается равным `message_id`
- Это означает, что `original_message_id` всегда указывает на сообщение, из которого капа была создана

### Для обновлений кап
- При обновлении существующей капы `original_message_id` не изменяется
- При создании новой капы для нового гео `original_message_id` наследуется от оригинальной капы
- Это позволяет отслеживать, из какого изначального сообщения была создана вся группа кап

### Для истории кап
- При создании записи в истории `original_message_id` копируется из основной капы
- Это позволяет всегда знать, к какому изначальному сообщению относилась капа в любой момент времени

## Преимущества

1. **Полное отслеживание**: Всегда можно найти оригинальное сообщение, из которого была создана капа
2. **Правильная группировка**: Все капы, созданные из одного сообщения, имеют одинаковый `original_message_id`
3. **Историческая связь**: В истории кап сохраняется связь с оригинальным сообщением
4. **Обратная совместимость**: Существующие записи могут быть обновлены командой

## Использование

### Обновление существующих записей

```bash
# Windows
populate-original-message-id.bat

# Linux/Mac
php artisan populate:original-message-id
```

### Тестирование

```bash
# Windows
test-original-message-id.bat

# Linux/Mac
php test_original_message_id.php
```

## Структура данных

### Таблица caps
- `message_id` - ID сообщения, с которым связана капа
- `original_message_id` - ID оригинального сообщения, из которого была создана капа

### Таблица caps_history
- `message_id` - ID сообщения, при котором была создана историческая запись
- `original_message_id` - ID оригинального сообщения, из которого была создана капа

### Связи в моделях

```php
// В модели Cap
public function originalMessage()
{
    return $this->belongsTo(Message::class, 'original_message_id');
}

// В модели CapHistory
public function originalMessage()
{
    return $this->belongsTo(Message::class, 'original_message_id');
}
```

## Примеры использования

### Поиск всех кап, созданных из одного сообщения

```php
$originalMessageId = 12345;
$caps = Cap::where('original_message_id', $originalMessageId)->get();
```

### Получение истории кап с оригинальным сообщением

```php
$history = CapHistory::with('originalMessage')->where('cap_id', $capId)->get();
```

### Проверка принадлежности кап к одной группе

```php
$cap1 = Cap::find(1);
$cap2 = Cap::find(2);

if ($cap1->original_message_id == $cap2->original_message_id) {
    echo "Капы созданы из одного сообщения";
}
``` 