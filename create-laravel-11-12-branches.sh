#!/bin/bash

# Скрипт для создания веток Laravel 11/12 в ваших форках
# Использование: ./create-laravel-11-12-branches.sh

echo "🚀 Создание веток Laravel 11/12 для ваших форков..."

# Массив ваших репозиториев
declare -A repos=(
    ["scout-elasticsearch-driver"]="https://github.com/Serg28/scout-elasticsearch-driver.git"
    ["LaravelShoppingcart"]="https://github.com/Serg28/LaravelShoppingcart.git"
    ["laravel-liqpay"]="https://github.com/Serg28/laravel-liqpay.git"
    ["laravel-wayforpay"]="https://github.com/Serg28/laravel-wayforpay.git"
    ["commentify"]="https://github.com/Serg28/commentify.git"
    ["redirectmap"]="https://github.com/Serg28/redirectmap.git"
)

# Функция для обновления composer.json
update_composer_json() {
    local repo_name=$1
    
    echo "  📝 Обновляем composer.json для $repo_name..."
    
    if [ -f "composer.json" ]; then
        # Создаем резервную копию
        cp composer.json composer.json.backup
        
        # Обновляем PHP версию
        sed -i 's/"php": "\^8\.[01]"/"php": "^8.2"/g' composer.json
        
        # Обновляем Laravel Framework
        sed -i 's/"laravel\/framework": "\^10"/"laravel\/framework": "^11.0|^12.0"/g' composer.json
        sed -i 's/"laravel\/framework": "\^9"/"laravel\/framework": "^11.0|^12.0"/g' composer.json
        sed -i 's/"laravel\/framework": "\^8"/"laravel\/framework": "^11.0|^12.0"/g' composer.json
        
        # Обновляем другие зависимости
        sed -i 's/"predis\/predis": "\^1\.[0-9]"/"predis\/predis": "^2.0"/g' composer.json
        sed -i 's/"nunomaduro\/collision": "\^7"/"nunomaduro\/collision": "^8.0"/g' composer.json
        sed -i 's/"phpunit\/phpunit": "\^9"/"phpunit\/phpunit": "^10.0"/g' composer.json
        
        echo "  ✅ composer.json обновлен"
    else
        echo "  ⚠️  composer.json не найден"
    fi
}

# Функция для создания README с информацией о Laravel 11/12
create_laravel_11_12_readme() {
    local repo_name=$1
    
    cat > "LARAVEL_11_12_SUPPORT.md" << EOF
# Laravel 11 & 12 Support

This branch provides support for Laravel 11 and 12.

## Requirements
- PHP 8.2+
- Laravel 11.0+ or Laravel 12.0+

## Installation

\`\`\`json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/Serg28/${repo_name}.git"
        }
    ],
    "require": {
        "package-name": "dev-laravel-11-12-support"
    }
}
\`\`\`

## Changes
- Updated PHP requirement to 8.2+
- Updated Laravel Framework to ^11.0|^12.0
- Updated other dependencies for compatibility

## Testing
Please test thoroughly before using in production.
EOF

    echo "  📄 Создан LARAVEL_11_12_SUPPORT.md"
}

# Основная функция для обработки репозитория
process_repository() {
    local repo_name=$1
    local repo_url=$2
    
    echo "🔧 Обрабатываем $repo_name..."
    
    # Клонируем репозиторий
    if [ -d "$repo_name" ]; then
        echo "  📁 Директория $repo_name уже существует, удаляем..."
        rm -rf "$repo_name"
    fi
    
    echo "  📥 Клонируем $repo_url..."
    git clone "$repo_url" "$repo_name"
    
    if [ $? -ne 0 ]; then
        echo "  ❌ Ошибка клонирования $repo_name"
        return 1
    fi
    
    cd "$repo_name"
    
    # Настраиваем git
    git config user.name "openhands"
    git config user.email "openhands@all-hands.dev"
    
    # Создаем новую ветку
    echo "  🌿 Создаем ветку laravel-11-12-support..."
    git checkout -b laravel-11-12-support
    
    # Обновляем файлы
    update_composer_json "$repo_name"
    create_laravel_11_12_readme "$repo_name"
    
    # Коммитим изменения
    git add .
    git commit -m "feat: Add Laravel 11 & 12 support

- Update PHP requirement to 8.2+
- Update Laravel Framework to ^11.0|^12.0
- Update dependencies for Laravel 11/12 compatibility
- Add Laravel 11/12 support documentation

BREAKING CHANGES:
- Minimum PHP version is now 8.2
- Laravel 11+ is now required"
    
    # Пушим ветку (закомментировано для безопасности)
    echo "  📤 Готово к пушу. Выполните вручную:"
    echo "     cd $repo_name && git push origin laravel-11-12-support"
    
    cd ..
    echo "  ✅ $repo_name обработан"
    echo ""
}

# Создаем рабочую директорию
WORK_DIR="laravel-11-12-forks"
mkdir -p "$WORK_DIR"
cd "$WORK_DIR"

echo "📁 Рабочая директория: $(pwd)"
echo ""

# Обрабатываем каждый репозиторий
for repo_name in "${!repos[@]}"; do
    repo_url="${repos[$repo_name]}"
    process_repository "$repo_name" "$repo_url"
done

echo "🎉 Все репозитории обработаны!"
echo ""
echo "📋 Следующие шаги:"
echo "1. Проверьте изменения в каждом репозитории"
echo "2. Протестируйте совместимость с Laravel 11/12"
echo "3. Запушьте ветки в GitHub:"
echo ""

for repo_name in "${!repos[@]}"; do
    echo "   cd $WORK_DIR/$repo_name && git push origin laravel-11-12-support"
done

echo ""
echo "4. Обновите ваш основной composer.json:"
echo ""
echo '   "require": {'
for repo_name in "${!repos[@]}"; do
    case $repo_name in
        "scout-elasticsearch-driver")
            echo '       "babenkoivan/scout-elasticsearch-driver": "dev-laravel-11-12-support",'
            ;;
        "LaravelShoppingcart")
            echo '       "bumbummen99/shoppingcart": "dev-laravel-11-12-support",'
            ;;
        "laravel-liqpay")
            echo '       "arturishe21/laravel-liqpay": "dev-laravel-11-12-support",'
            ;;
        "laravel-wayforpay")
            echo '       "maksa988/laravel-wayforpay": "dev-laravel-11-12-support",'
            ;;
        "commentify")
            echo '       "usamamuneerchaudhary/commentify": "dev-laravel-11-12-support",'
            ;;
        "redirectmap")
            echo '       "vis/redirectmap": "dev-laravel-11-12-support",'
            ;;
    esac
done
echo '   }'

echo ""
echo "🚀 Готово к миграции на Laravel 11/12!"