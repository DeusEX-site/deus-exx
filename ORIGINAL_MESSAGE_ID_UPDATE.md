# Обновление: Добавление поля original_message_id

## Описание проблемы
При обновлении капы `message_id` не меняется (остается старый), но при копировании в историю используется старый `message_id`, поэтому в истории показывается не то сообщение, которое привело к обновлению.

## Решение
Добавлено новое поле `original_message_id` в таблицы `caps` и `caps_history`, которое хранит ID сообщения, которое привело к обновлению капы.

## Файлы изменены

### Миграции (нужно выполнить)
- `database/migrations/2024_12_19_000014_add_original_message_id_to_caps_table.php`
- `database/migrations/2024_12_19_000015_add_original_message_id_to_caps_history_table.php`

### Модели
- `app/Models/Cap.php` - добавлено поле в fillable и связь originalMessage()
- `app/Models/CapHistory.php` - добавлено поле в fillable, связь и обновлен createFromCap()

### Логика обновления
- `app/Services/CapAnalysisService.php` - обновлены функции processCapUpdate() и getFieldsToUpdate()

### API
- `routes/web.php` - обновлен endpoint /api/cap-history/{capId} для использования original_message_id

## Как запустить обновление

1. Выполнить миграции:
```bash
php artisan migrate
```

2. После этого система будет:
   - При обновлении капы записывать `original_message_id` текущего сообщения обновления
   - При создании новой капы через обновление записывать `original_message_id`
   - В истории показывать правильное сообщение (из `original_message_id` если есть, иначе из `message_id`)

## Обратная совместимость
- Для существующих записей `original_message_id` будет `NULL`
- В API истории используется fallback: `original_message_id` → `message_id`
- Никаких изменений во фронтенде не требуется

## Проверка работы
После обновления попробуйте:
1. Обновить любую капу через reply
2. Посмотреть историю этой капы 
3. Убедиться, что отображается сообщение с обновлением, а не оригинальное 