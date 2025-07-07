# Обновление логики разделения кап по гео

## 🔄 Что изменилось

**Старая логика:**
- Affiliate и Recipient могли быть списками через запятую
- Создавались комбинации Affiliate × Recipient
- `Affiliate: A1, A2` + `Recipient: R1, R2` = 4 записи

**Новая логика:**
- Affiliate и Recipient - только одно значение
- Разделение происходит по геолокациям
- `Geo: GT PE MX` = 3 записи с одинаковыми настройками

## 📋 Новые правила

### Поля с одним значением
- **Affiliate**: `Affiliate: Webgate` (без запятых)
- **Recipient**: `Recipient: TradingM` (без запятых)

### Разделение по гео
- **Geo**: `Geo: GT PE MX` → 3 отдельные записи
- **Поддерживаемые разделители**: пробелы, запятые, слеши
- **Каждая запись**: содержит только один гео

## 🔧 Технические изменения

### app/Services/CapAnalysisService.php
```php
// Старая логика
foreach ($affiliates as $affiliate) {
    foreach ($recipients as $recipient) {
        // Создание комбинации
    }
}

// Новая логика
foreach ($baseData['geos'] as $geo) {
    $combination = $baseData;
    $combination['geos'] = [$geo]; // Только один гео
    $combinations[] = $combination;
}
```

### Примеры парсинга
```php
// Geo с разными разделителями
"Geo: GT PE MX"      → ["GT", "PE", "MX"]
"Geo: IE, DE, FR"    → ["IE", "DE", "FR"]  
"Geo: RU/UA/KZ"      → ["RU", "UA", "KZ"]
"Geo: US,CA AU"      → ["US", "CA", "AU"]
```

## 📊 Примеры работы

### Пример 1: Множественные гео
**Входное сообщение:**
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

**Результат в базе данных:**
```
Запись 1: Webgate - TradingM (Cap: 20, Geo: GT, Date: 24.02.2024)
Запись 2: Webgate - TradingM (Cap: 20, Geo: PE, Date: 24.02.2024)
Запись 3: Webgate - TradingM (Cap: 20, Geo: MX, Date: 24.02.2024)
```

### Пример 2: Одно гео
**Входное сообщение:**
```
Affiliate: TestAffiliate
Recipient: TestBroker
Cap: 30
Geo: RU
```

**Результат в базе данных:**
```
Запись 1: TestAffiliate - TestBroker (Cap: 30, Geo: RU)
```

### Пример 3: Без гео
**Входное сообщение:**
```
Affiliate: TestAffiliate
Recipient: TestBroker
Cap: 25
Geo: 
```

**Результат в базе данных:**
```
Запись 1: TestAffiliate - TestBroker (Cap: 25, Geo: [])
```

## 🎯 Преимущества новой логики

1. **Простота**: Нет сложных комбинаций Affiliate × Recipient
2. **Логичность**: Разделение по реальному критерию (география)
3. **Производительность**: Меньше записей в базе данных
4. **Понятность**: Каждая запись = одна капа для одного гео

## 📝 Что нужно помнить

- **Affiliate и Recipient** - всегда одно значение
- **Geo** - можно несколько через пробелы/запятые/слеши
- **Каждое гео** = отдельная запись в базе данных
- **Все остальные поля** одинаковы для всех записей одного сообщения

## 🚀 Команды для проверки

```bash
# Тестирование новой логики
php artisan update:new-cap-format --test

# Проверка созданных записей
php artisan test:new-cap-system
```

## ✅ Статус

- ✅ Логика изменена в `CapAnalysisService.php`
- ✅ Тесты обновлены в `UpdateToNewCapFormat.php`
- ✅ Документация обновлена в `NEW_CAP_FORMAT_README.md`
- ✅ Примеры обновлены во всех файлах

🎉 **Новая логика готова к использованию!** 