# Анализ Капы (Cap Analysis)

## Описание

Новая функция анализа капы предназначена для автоматического сбора и анализа сообщений из Telegram чатов, которые содержат информацию о лимитах (капах) в арбитражном трафике.

## Основные возможности

### 1. Поиск сообщений
Система автоматически находит сообщения, содержащие следующие ключевые слова:
- `cap`, `CAP`
- `сар`, `САР` 
- `сар`, `САР`
- `кап`, `КАП`

### 2. Анализ паттернов

#### Расписание работы
- **24/7 или 24-7** - капа работает круглосуточно
- **10-19, 09-18** и т.д. - время работы капы (через дефис)

#### Даты
- **14.05, 25.12** - даты в формате DD.MM (через точку)
- Если даты нет, то капа считается безвременной

#### Числа и лимиты
- **Числа после слова cap/сар/сар** - это капа (лимит на партнера)
- **Большие числа без cap** - общий объем (не может быть меньше капы)

#### Структура сообщения
```
cap 30 НазваниеАффилейта - НазваниеБрокера : гео1,гео2,гео3
cap 30 НазваниеАффилейта - НазваниеБрокера : гео1,гео2,гео3
24/7
```

### 3. Типы лимитов

#### Тип 1: Без общего лимита
```
cap 30 XYZ Affiliate - BinaryBroker : DE,FR,UK
24/7
```

#### Тип 2: С общим лимитом
```
Общий объем 500 лидов
cap 50 TestAffiliate - CryptoTrader : RU,UA,KZ
10-18
```

## Как использовать

### 1. Доступ к функции
- Войдите в систему
- На главной странице (Dashboard) нажмите кнопку **"📊 Анализ Капы"**

### 2. Поиск и фильтрация
- **Дополнительный поиск**: введите текст для дополнительной фильтрации
- **Выбор чата**: выберите конкретный чат или оставьте "Все чаты"
- Нажмите **"🔍 Найти"**

### 3. Анализ результатов
Система отобразит:
- **Общую статистику**: количество сообщений, капы, расписания, гео
- **Детальный анализ каждого сообщения**:
  - Наличие слова cap/сар/сар
  - Размер капы
  - Общий лимит
  - Расписание работы
  - Дата
  - Название аффилейта
  - Название брокера
  - Список гео

### 4. Экспорт данных
- Нажмите **"💾 Экспорт CSV"** для сохранения результатов в Excel/CSV файл

## Примеры сообщений

### Пример 1: Полная информация
```
cap 30 XYZ Affiliate - BinaryBroker : DE,FR,UK
24/7
```
**Анализ:**
- Капа: 30
- Аффилейт: XYZ Affiliate
- Брокер: BinaryBroker
- Гео: DE, FR, UK
- Расписание: 24/7

### Пример 2: С временным ограничением
```
сар 50 TestAffiliate - CryptoTrader : RU,UA,KZ 10-18
```
**Анализ:**
- Капа: 50
- Аффилейт: TestAffiliate
- Брокер: CryptoTrader
- Гео: RU, UA, KZ
- Расписание: 10-18

### Пример 3: С датой
```
cap 100 SuperAffiliate - BinaryOptions : IT,ES,PT 14.05
```
**Анализ:**
- Капа: 100
- Аффилейт: SuperAffiliate
- Брокер: BinaryOptions
- Гео: IT, ES, PT
- Дата: 14.05

### Пример 4: С общим лимитом
```
Общий объем 500 лидов на сегодня
cap 50 MyAffiliate - ForexPro : US,CA,AU
```
**Анализ:**
- Капа: 50
- Общий лимит: 500
- Аффилейт: MyAffiliate
- Брокер: ForexPro
- Гео: US, CA, AU

## Тестирование

### Создание тестовых данных
```bash
php artisan test:cap-analysis
```

Эта команда создаст тестовые сообщения и проверит работу анализатора.

## API Endpoints

### GET /api/cap-analysis
**Параметры:**
- `search` (optional) - дополнительный поиск по тексту
- `chat_id` (optional) - ID конкретного чата

**Ответ:**
```json
{
  "success": true,
  "messages": [
    {
      "id": 1,
      "message": "cap 30 XYZ Affiliate - BinaryBroker : DE,FR,UK",
      "user": "TestUser",
      "chat_id": 1,
      "chat_name": "Test Chat",
      "timestamp": "25.12.2024 15:30:45",
      "analysis": {
        "has_cap_word": true,
        "cap_amount": 30,
        "total_amount": null,
        "schedule": "24/7",
        "date": null,
        "is_24_7": true,
        "affiliate_name": "XYZ Affiliate",
        "broker_name": "BinaryBroker",
        "geos": ["DE", "FR", "UK"],
        "work_hours": null,
        "raw_numbers": [30]
      }
    }
  ],
  "total": 1
}
```

## Поддерживаемые языки

- **Английский**: cap, CAP
- **Русский**: сар, САР, кап, КАП
- **Украинский**: сар, САР

## Автоматизация

Система автоматически:
1. Сканирует все сообщения в базе данных
2. Выявляет паттерны капы
3. Извлекает структурированные данные
4. Предоставляет возможность экспорта

Это позволяет значительно ускорить процесс сбора и анализа капы для арбитражного трафика. 