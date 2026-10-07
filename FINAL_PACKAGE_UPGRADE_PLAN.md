# 📋 План обновления пакетов для Laravel 11 & 12

## 🎯 Результаты анализа вашего composer.json

### ✅ Пакеты готовые к Laravel 11/12 (4)
- `akaunting/laravel-firewall` - ✅ Поддерживает ^11.0|^12.0
- `marcin-orlowski/laravel-api-response-builder` - ✅ Поддерживает ^11.0
- `laravel/octane` - ✅ Совместим
- `laravel/telescope` - ✅ Совместим

### ❌ Критические обновления (обязательно)

#### 1. Основные зависимости
```json
{
    "require": {
        "php": "^8.2",                    // Было: ^8.1
        "laravel/framework": "^11.0",     // Было: ^10
        "predis/predis": "^2.0"           // Было: ^1.1
    },
    "require-dev": {
        "nunomaduro/collision": "^8.0",   // Было: ^7
        "phpunit/phpunit": "^10.0"        // Было: ^9.5.10
    }
}
```

### ⚠️ Ваши форки (требуют создания веток Laravel 11/12)

#### Приоритет 1 - Критичные для работы
```json
{
    "require": {
        "vis/builder_lara_5": "dev-laravel-11-12-support",                    // ✅ Готово
        "babenkoivan/scout-elasticsearch-driver": "dev-laravel-11-12-support", // ❌ Нужна ветка
        "bumbummen99/shoppingcart": "dev-laravel-11-12-support",               // ❌ Нужна ветка
        "arturishe21/laravel-liqpay": "dev-laravel-11-12-support",             // ❌ Нужна ветка
        "maksa988/laravel-wayforpay": "dev-laravel-11-12-support",             // ❌ Нужна ветка
        "usamamuneerchaudhary/commentify": "dev-laravel-11-12-support"         // ❌ Нужна ветка
    }
}
```

### 🔍 Пакеты требующие ручной проверки

#### Высокий приоритет (используются активно)
1. **`livewire/livewire: ^3`** - Проверить совместимость с Laravel 11/12
2. **`maatwebsite/excel: ^3.1`** - Обновить до последней версии
3. **`spatie/once: ^3.1`** - Проверить совместимость
4. **`laravel/horizon: ^5`** - Возможно нужна версия ^5.2
5. **`laravel/sanctum: ^3.0`** - Проверить совместимость
6. **`laravel/socialite: ^5.2`** - Обновить до ^5.15

#### Средний приоритет
7. **`arrilot/laravel-widgets: ^3.14`** - Найти альтернативу или форк
8. **`biscolab/laravel-recaptcha: ^6`** - Обновить до ^7 или ^8
9. **`hisorange/browser-detect: ^4.5`** - Обновить до ^5.0
10. **`wire-elements/modal: ^2.0`** - Проверить совместимость с Livewire 3

#### Низкий приоритет (вспомогательные)
11. **`fouladgar/laravel-otp: 4.3.0.*`** - Обновить до ^5.5
12. **`rtconner/laravel-likeable: ~3.0`** - Проверить совместимость
13. **`vxm/laravel-async: ^4`** - Проверить совместимость

## 🚀 Пошаговый план миграции

### Этап 1: Подготовка (1-2 дня)
1. **Создайте ветки Laravel 11/12 для ваших форков**:
   ```bash
   ./create-laravel-11-12-branches.sh
   ```

2. **Проверьте совместимость окружения**:
   ```bash
   php check-compatibility.php
   ```

### Этап 2: Обновление основных зависимостей (1 день)
```json
{
    "require": {
        "php": "^8.2",
        "laravel/framework": "^11.0",
        "predis/predis": "^2.0",
        
        // Обновленные версии
        "laravel/socialite": "^5.15",
        "fouladgar/laravel-otp": "^5.5",
        "hisorange/browser-detect": "^5.0"
    },
    "require-dev": {
        "nunomaduro/collision": "^8.0",
        "phpunit/phpunit": "^10.0"
    }
}
```

### Этап 3: Обновление ваших форков (2-3 дня)
```json
{
    "require": {
        "vis/builder_lara_5": "dev-laravel-11-12-support",
        "babenkoivan/scout-elasticsearch-driver": "dev-laravel-11-12-support",
        "bumbummen99/shoppingcart": "dev-laravel-11-12-support",
        "arturishe21/laravel-liqpay": "dev-laravel-11-12-support",
        "maksa988/laravel-wayforpay": "dev-laravel-11-12-support",
        "usamamuneerchaudhary/commentify": "dev-laravel-11-12-support"
    }
}
```

### Этап 4: Проблемные пакеты (3-5 дней)

#### Пакеты для замены/обновления:
1. **`arrilot/laravel-widgets`** - Рассмотреть альтернативы:
   - `spatie/laravel-view-components`
   - Собственные Blade компоненты
   - Livewire компоненты

2. **`biscolab/laravel-recaptcha`** - Обновить:
   ```bash
   composer require "biscolab/laravel-recaptcha:^7.0"
   ```

3. **`rtconner/laravel-likeable`** - Проверить форки или альтернативы

## 📝 Обновленный composer.json

```json
{
    "name": "laravel/laravel",
    "type": "project",
    "description": "mercurio-cms",
    "keywords": ["framework", "laravel"],
    "license": "MIT",
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/Serg28/scout-elasticsearch-driver.git"
        },
        {
            "type": "vcs",
            "url": "https://github.com/Serg28/commentify.git"
        },
        {
            "type": "vcs",
            "url": "https://github.com/Serg28/builder_lara_5"
        },
        {
            "type": "vcs",
            "url": "https://github.com/Serg28/LaravelShoppingcart.git"
        },
        {
            "type": "vcs",
            "url": "https://github.com/Serg28/laravel-wayforpay.git"
        },
        {
            "type": "vcs",
            "url": "https://github.com/Serg28/laravel-liqpay.git"
        },
        {
            "type": "vcs",
            "url": "https://github.com/Serg28/redirectmap.git"
        }
    ],
    "require": {
        "php": "^8.2",
        "laravel/framework": "^11.0",
        
        // Обновленные основные пакеты
        "predis/predis": "^2.0",
        "laravel/socialite": "^5.15",
        "fouladgar/laravel-otp": "^5.5",
        "hisorange/browser-detect": "^5.0",
        "biscolab/laravel-recaptcha": "^7.0",
        
        // Ваши обновленные форки
        "vis/builder_lara_5": "dev-laravel-11-12-support",
        "babenkoivan/scout-elasticsearch-driver": "dev-laravel-11-12-support",
        "bumbummen99/shoppingcart": "dev-laravel-11-12-support",
        "arturishe21/laravel-liqpay": "dev-laravel-11-12-support",
        "maksa988/laravel-wayforpay": "dev-laravel-11-12-support",
        "usamamuneerchaudhary/commentify": "dev-laravel-11-12-support",
        
        // Остальные пакеты (проверить совместимость)
        "aaronfrancis/fast-paginate": "^2.0",
        "akaunting/laravel-firewall": "^2",
        "alphasms/gateway": "dev-master",
        "daaner/turbosms": "^1.35",
        "danharrin/livewire-rate-limiting": "^2.0",
        "dedoc/scramble": "^0.12",
        "deeplcom/deepl-php": "^1.12",
        "doctrine/dbal": "^3.7",
        "elasticsearch/elasticsearch": "^7.7",
        "guzzlehttp/guzzle": "^7.0.1",
        "huy-nguyen/laravel-optimize-init-db-connection": "^1.0",
        "iksaku/laravel-mass-update": "^2.0",
        "kfoobar/flush-horizon": "^1.0",
        "kwn/number-to-words": "^2.2",
        "laragear/preload": "^3.0",
        "laravel/horizon": "^5.2",
        "laravel/octane": "^2",
        "laravel/pulse": "^1",
        "laravel/sanctum": "^3.0",
        "laravel/scout": "*",
        "livewire/livewire": "^3",
        "maatwebsite/excel": "^3.1",
        "marcin-orlowski/laravel-api-response-builder": "^11",
        "opis/closure": "^3.6",
        "plakidan/monobank-pay": "^0.2.0",
        "psr/simple-cache": "^2.0",
        "rodenastyle/stream-parser": "^1.4",
        "rtconner/laravel-likeable": "~3.0",
        "serg28/laravel-self-diagnosis": "dev-master",
        "serg28/livewire-access": "dev-master",
        "spatie/once": "^3.1",
        "symfony/http-client": "7.1.8.0",
        "tinymce/tinymce": "7.2.0.0",
        "vis/artur_image_storage_l5": "2.*",
        "vis/full_cache": "1.*",
        "vis/redirectmap": "dev-laravel-11-12-support",
        "vxm/laravel-async": "^4",
        "wire-elements/modal": "^2.0",
        "zoha/laravel-meta": "^2.0"
    },
    "require-dev": {
        "barryvdh/laravel-ide-helper": "^3",
        "beyondcode/laravel-query-detector": "^2",
        "fakerphp/faker": "^1.9.1",
        "jetbrains/phpstorm-attributes": "^1",
        "laravel/envoy": "^2.7",
        "laravel/pint": "^1.0",
        "laravel/sail": "^1.0.1",
        "laravel/telescope": "^5.2",
        "laravel/tinker": "^2.7",
        "larastan/larastan": "^3.0",
        "maantje/xhprof-buggregator-laravel": "dev-main",
        "mbezhanov/faker-provider-collection": "^2.0",
        "mockery/mockery": "^1.4.4",
        "nunomaduro/collision": "^8.0",
        "nunomaduro/phpinsights": "^2.12",
        "phpunit/phpunit": "^10.0",
        "slevomat/coding-standard": "^8",
        "spatie/laravel-ignition": "^2.0",
        "squizlabs/php_codesniffer": "^3.6"
    }
}
```

## ⚡ Команды для быстрого старта

### 1. Создание веток для форков
```bash
./create-laravel-11-12-branches.sh
```

### 2. Обновление основных зависимостей
```bash
composer require "php:^8.2" "laravel/framework:^11.0" "predis/predis:^2.0"
composer require --dev "nunomaduro/collision:^8.0" "phpunit/phpunit:^10.0"
```

### 3. Обновление проблемных пакетов
```bash
composer require "laravel/socialite:^5.15"
composer require "fouladgar/laravel-otp:^5.5"
composer require "hisorange/browser-detect:^5.0"
composer require "biscolab/laravel-recaptcha:^7.0"
```

### 4. Переключение на ваши обновленные форки
```bash
composer require "vis/builder_lara_5:dev-laravel-11-12-support"
composer require "babenkoivan/scout-elasticsearch-driver:dev-laravel-11-12-support"
# ... и так далее для всех форков
```

## 🧪 Тестирование

### Создайте тестовое окружение
```bash
# 1. Клонируйте проект
git clone your-project.git laravel-11-test

# 2. Переключитесь на ветку тестирования
cd laravel-11-test
git checkout -b laravel-11-upgrade

# 3. Обновите composer.json
# 4. Установите зависимости
composer install

# 5. Запустите тесты
php artisan test
```

## 📊 Оценка времени

- **Подготовка веток**: 1-2 дня
- **Основные обновления**: 1 день  
- **Форки**: 2-3 дня
- **Проблемные пакеты**: 3-5 дней
- **Тестирование**: 2-3 дня

**Общее время**: 9-14 дней

## 🎯 Приоритеты

### Высокий приоритет (сделать в первую очередь)
1. PHP 8.2+
2. Laravel Framework ^11.0
3. Ваши форки (builder, scout, cart, payments)
4. Predis ^2.0

### Средний приоритет
1. Laravel ecosystem пакеты
2. Livewire и связанные пакеты
3. Dev dependencies

### Низкий приоритет
1. Вспомогательные утилиты
2. Редко используемые пакеты

---

**🚀 Готовы начать миграцию на Laravel 11 & 12!**