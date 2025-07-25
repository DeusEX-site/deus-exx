# Обновление системы Reply для поддержки многоколоночных связей

## Описание обновлений

В системе обновлены методы работы с обновлениями капов через reply (ответ на сообщение) для поддержки многоколоночных связей.

### Изменения в коде

1. **Обновлен метод `isUpdateCapMessage`**:
   - Теперь не требует обязательного наличия Geo в сообщении
   - Проверяет только отсутствие Affiliate или Recipient

2. **Полностью переработан метод `processCapUpdate`**:
   - Поддерживает парсинг множественных значений всех полей (как в первом методе)
   - Обрабатывает многоколоночные связи с привязкой по порядку
   - Автоматически использует гео из оригинальной капы, если:
     - В сообщении не указаны Geo
     - В оригинальной капе только одно гео
   - Выдает ошибку если Geo не указаны, а в оригинальной капе несколько гео

### Поддерживаемые сценарии обновления

1. **Обновление с указанием всех гео**:
   ```
   Geo: DE AT CH
   Total: 600 1200 2000
   Pending ACQ: yes, no, yes
   ```

2. **Обновление с частичным указанием гео**:
   ```
   Geo: DE CH
   Cap: 150 350
   Schedule: 24/7
   ```

3. **Обновление капы с одним гео БЕЗ указания Geo**:
   ```
   Cap: 75
   Total: 150
   Schedule: 09:00/18:00 GMT+01:00
   ```

### Правила обработки многоколоночных связей

1. **Привязка по порядку**: Значения полей привязываются к гео по порядку (первое значение к первому гео и т.д.)

2. **Одно значение на все**: Если для поля указано только одно значение, оно применяется ко всем гео

3. **Проверка количества**: Для поля Cap количество значений должно совпадать с количеством Geo

4. **Проверка цепочки**: Система проверяет, что обновляемые капы относятся к той же цепочке сообщений

### Тестирование

Для проверки работы системы используйте:
```
test-reply-update-system.bat
```

Тест проверяет:
- Создание капы с несколькими гео
- Обновление всех гео через reply
- Частичное обновление только некоторых гео
- Обновление капы с одним гео без указания Geo
- Правильную обработку ошибок
- Создание записей в истории изменений 