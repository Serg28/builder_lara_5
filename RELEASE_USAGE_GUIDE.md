# Использование релизов Builder CMS

## 🏷️ Доступные версии

### Версия 4.0.0 - Laravel 11 & 12 Support
- **Тег**: `v4.0.0`
- **Поддержка**: Laravel 11.x, 12.x
- **PHP**: 8.2+
- **Статус**: Стабильная версия для Laravel 11/12

### Версия 3.x - Laravel 5-10 Support  
- **Тег**: `v3.*`
- **Поддержка**: Laravel 5.x - 10.x
- **PHP**: 7.x - 8.1
- **Статус**: Поддерживается для старых версий Laravel

## 📦 Установка через релизы

### Для Laravel 11 & 12 (рекомендуется)
```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/Serg28/builder_lara_5"
        }
    ],
    "require": {
        "vis/builder_lara_5": "^4.0"
    }
}
```

### Конкретная версия 4.0.0
```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/Serg28/builder_lara_5"
        }
    ],
    "require": {
        "vis/builder_lara_5": "4.0.0"
    }
}
```

### Для старых версий Laravel
```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/Serg28/builder_lara_5"
        }
    ],
    "require": {
        "vis/builder_lara_5": "^3.0"
    }
}
```

## 🔄 Команды установки

### Установка последней версии 4.x
```bash
composer require "vis/builder_lara_5:^4.0"
```

### Установка конкретной версии
```bash
composer require "vis/builder_lara_5:4.0.0"
```

### Обновление до версии 4.x
```bash
# Обновите composer.json, затем:
composer update vis/builder_lara_5
```

## 🎯 Преимущества использования релизов

### ✅ Стабильность
- Фиксированная версия кода
- Протестированные изменения
- Предсказуемое поведение

### ✅ Безопасность
- Нет неожиданных изменений
- Контролируемые обновления
- Возможность отката

### ✅ Производительность
- Composer кэширует релизы
- Быстрая установка
- Меньше сетевых запросов

## 📋 Сравнение подходов

| Подход | Стабильность | Обновления | Использование |
|--------|--------------|------------|---------------|
| `^4.0` | ✅ Высокая | 🔄 Автоматические минорные | 🏭 Продакшен |
| `4.0.0` | ✅ Максимальная | ❌ Только ручные | 🔒 Критичные проекты |
| `dev-laravel-11-12-support` | ⚠️ Средняя | ✅ Все изменения | 🧪 Разработка/тестирование |

## 🔧 Настройка для разных окружений

### composer.json для продакшена
```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/Serg28/builder_lara_5"
        }
    ],
    "require": {
        "vis/builder_lara_5": "^4.0"
    },
    "minimum-stability": "stable",
    "prefer-stable": true
}
```

### composer.json для разработки
```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/Serg28/builder_lara_5"
        }
    ],
    "require": {
        "vis/builder_lara_5": "^4.0"
    },
    "require-dev": {
        "vis/builder_lara_5": "dev-laravel-11-12-support"
    },
    "minimum-stability": "dev",
    "prefer-stable": true
}
```

## 🚀 Миграция на релиз

### Из dev-ветки на релиз
```bash
# 1. Обновите composer.json
# Замените: "vis/builder_lara_5": "dev-laravel-11-12-support"
# На: "vis/builder_lara_5": "^4.0"

# 2. Обновите пакет
composer update vis/builder_lara_5

# 3. Проверьте версию
composer show vis/builder_lara_5
```

### Из старой версии на 4.0
```bash
# 1. Проверьте совместимость
php vendor/vis/builder_lara_5/check-compatibility.php

# 2. Обновите composer.json
# "vis/builder_lara_5": "^4.0"

# 3. Запустите обновление
composer update vis/builder_lara_5

# 4. Выполните миграцию
bash vendor/vis/builder_lara_5/upgrade-to-laravel-11-12.sh
```

## 📊 Проверка установленной версии

### Через Composer
```bash
composer show vis/builder_lara_5
```

### Через PHP
```php
$version = \Composer\InstalledVersions::getVersion('vis/builder_lara_5');
echo "Установленная версия: " . $version;
```

### В коде приложения
```php
// В ServiceProvider или контроллере
$builderVersion = config('builder.version', 'unknown');
```

## 🔄 Стратегия версионирования

### Semantic Versioning (SemVer)
- **4.0.0** - Мажорная версия (breaking changes)
- **4.1.0** - Минорная версия (новые функции)
- **4.0.1** - Патч версия (исправления)

### Рекомендуемые ограничения
```json
{
    "require": {
        "vis/builder_lara_5": "^4.0"    // Рекомендуется
        // "vis/builder_lara_5": "~4.0.0" // Только патчи
        // "vis/builder_lara_5": "4.0.*"  // Только патчи (альтернатива)
        // "vis/builder_lara_5": "4.0.0"  // Точная версия
    }
}
```

## 🛠️ Создание собственных релизов

Если вы форкнули репозиторий:

```bash
# 1. Создайте тег
git tag -a v4.0.1 -m "Custom release v4.0.1"

# 2. Отправьте тег
git push origin v4.0.1

# 3. Используйте в composer.json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/YOUR_USERNAME/builder_lara_5"
        }
    ],
    "require": {
        "vis/builder_lara_5": "^4.0"
    }
}
```

## 📚 Дополнительные ресурсы

- [Composer Versions](https://getcomposer.org/doc/articles/versions.md)
- [Semantic Versioning](https://semver.org/)
- [Laravel Package Development](https://laravel.com/docs/packages)

---

**Рекомендация**: Используйте `^4.0` для продакшена и `dev-laravel-11-12-support` для разработки новых функций.