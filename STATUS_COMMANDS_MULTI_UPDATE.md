# Обновление команд статуса для работы с множественными капами

## Описание обновлений

Обновлена логика работы команд статуса (RUN, STOP, DELETE, RESTORE) для поддержки применения к нескольким капам одновременно.

### Основные изменения

1. **Простая команда без Geo**:
   - Если команда отправлена как ответ на сообщение с капами без указания Geo
   - Команда применяется ко ВСЕМ капам в оригинальном сообщении
   - Пример: просто `STOP` в ответ на сообщение с 3 капами остановит все 3

2. **Команда с указанием Geo**:
   - Если в команде указан параметр Geo
   - Команда применяется только к капе с указанным гео
   - Пример: `Geo: DE\nRUN` активирует только капу с гео DE

### Поддерживаемые сценарии

#### 1. Применение команды ко всем капам
```
Ответ на сообщение с капами:
STOP
```
Результат: Все капы в сообщении будут остановлены

#### 2. Применение команды к конкретной капе
```
Ответ на сообщение с капами:
Geo: DE
RUN
```
Результат: Только капа с гео DE будет активирована

#### 3. Старый формат (для обратной совместимости)
```
Affiliate: G06
Recipient: TMedia
Cap: 100
Geo: DE
DELETE
```
Результат: Удаление конкретной капы по полным параметрам

### Правила применения команд

- **RUN**: можно применить только к остановленным капам (статус STOP)
- **STOP**: можно применить только к активным капам (статус RUN)
- **DELETE**: можно применить к активным или остановленным капам (статус RUN или STOP)
- **RESTORE**: можно применить только к удаленным капам (статус DELETE)

### История изменений

- Каждое изменение статуса сохраняется в истории (таблица caps_history)
- При применении команды ко многим капам создается запись истории для каждой

### Сообщения об обновлении

- При обновлении одной капы: "Капа G06 → TMedia (DE, 100) остановлена"
- При обновлении нескольких: "Обновлено кап: 3. G06 → TMedia (DE, 100) остановлена; G06 → TMedia (AT, 200) остановлена; G06 → TMedia (CH, 300) остановлена"

### Тестирование

Для проверки работы используйте:
```
test-status-commands-multi.bat
```

Тест проверяет:
- Применение команды ко всем капам
- Применение команды к конкретной капе по Geo
- Правильную работу всех команд (RUN, STOP, DELETE, RESTORE)
- Создание записей в истории 