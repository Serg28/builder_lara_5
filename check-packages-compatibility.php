<?php

/**
 * Скрипт проверки совместимости пакетов с Laravel 11 & 12
 */

echo "🔍 Проверка совместимости пакетов с Laravel 11/12...\n\n";

// Пакеты из вашего composer.json для проверки
$packages = [
    // Основные пакеты
    'aaronfrancis/fast-paginate' => '^1.0',
    'akaunting/laravel-firewall' => '^2',
    'arrilot/laravel-widgets' => '^3.14',
    'biscolab/laravel-recaptcha' => '^6',
    'danharrin/livewire-rate-limiting' => '^1.3',
    'dedoc/scramble' => '^0.12',
    'deeplcom/deepl-php' => '^1.11',
    'fouladgar/laravel-otp' => '4.3.0.*',
    'hisorange/browser-detect' => '^4.5',
    'huy-nguyen/laravel-optimize-init-db-connection' => '^1.0',
    'iksaku/laravel-mass-update' => '^1.0',
    'kfoobar/flush-horizon' => '^1.0',
    'kwn/number-to-words' => '^2.2',
    'laragear/preload' => '^2',
    'marcin-orlowski/laravel-api-response-builder' => '^10',
    'plakidan/monobank-pay' => '^0.2.0',
    'rodenastyle/stream-parser' => '^1.4',
    'rtconner/laravel-likeable' => '~3.0',
    'vxm/laravel-async' => '^4',
    'wire-elements/modal' => '^2.0',
    'zoha/laravel-meta' => '^2.0',
    
    // Laravel ecosystem
    'laravel/horizon' => '^5',
    'laravel/octane' => '^2',
    'laravel/pulse' => '^1',
    'laravel/sanctum' => '^3.0',
    'laravel/socialite' => '^5.2',
    'livewire/livewire' => '^3',
    'maatwebsite/excel' => '^3.1',
    'spatie/once' => '^3.1',
    
    // Dev dependencies
    'barryvdh/laravel-ide-helper' => '^3',
    'beyondcode/laravel-query-detector' => '^2',
    'laravel/telescope' => '^5.0',
    'laravel/pint' => '^1.0',
    'laravel/sail' => '^1.0.1',
    'larastan/larastan' => '^2.0',
    'nunomaduro/collision' => '^7',
    'spatie/laravel-ignition' => '^2.0',
];

$results = [
    'compatible' => [],
    'needs_update' => [],
    'unknown' => [],
    'error' => []
];

function checkPackageCompatibility($packageName) {
    $url = "https://packagist.org/packages/{$packageName}.json";
    
    $context = stream_context_create([
        'http' => [
            'timeout' => 10,
            'user_agent' => 'Laravel-Compatibility-Checker/1.0'
        ]
    ]);
    
    $data = @file_get_contents($url, false, $context);
    
    if (!$data) {
        return ['status' => 'error', 'message' => 'Не удалось получить данные'];
    }
    
    $packageData = json_decode($data, true);
    
    if (!$packageData || !isset($packageData['package']['versions'])) {
        return ['status' => 'error', 'message' => 'Неверный формат данных'];
    }
    
    $versions = $packageData['package']['versions'];
    $latestStable = null;
    $hasLaravel11Support = false;
    $hasLaravel12Support = false;
    
    // Ищем последнюю стабильную версию и проверяем поддержку Laravel 11/12
    foreach ($versions as $version => $versionData) {
        if (strpos($version, 'dev-') === 0) continue;
        
        if (!$latestStable) {
            $latestStable = $versionData;
        }
        
        if (isset($versionData['require']['laravel/framework'])) {
            $laravelReq = $versionData['require']['laravel/framework'];
            
            // Проверяем поддержку Laravel 11
            if (preg_match('/\^11|\|.*11|>=.*11/', $laravelReq)) {
                $hasLaravel11Support = true;
            }
            
            // Проверяем поддержку Laravel 12
            if (preg_match('/\^12|\|.*12|>=.*12/', $laravelReq)) {
                $hasLaravel12Support = true;
            }
        }
    }
    
    if (!$latestStable) {
        return ['status' => 'error', 'message' => 'Стабильная версия не найдена'];
    }
    
    $laravelRequirement = $latestStable['require']['laravel/framework'] ?? 'не указано';
    $latestVersion = $latestStable['version'] ?? 'неизвестно';
    
    if ($hasLaravel11Support || $hasLaravel12Support) {
        $status = 'compatible';
        $message = "Поддерживает Laravel " . ($hasLaravel11Support ? "11" : "") . ($hasLaravel11Support && $hasLaravel12Support ? " и " : "") . ($hasLaravel12Support ? "12" : "");
    } elseif (preg_match('/\^10|\^9|\^8/', $laravelRequirement)) {
        $status = 'needs_update';
        $message = "Поддерживает только старые версии Laravel";
    } else {
        $status = 'unknown';
        $message = "Требования Laravel неясны";
    }
    
    return [
        'status' => $status,
        'message' => $message,
        'latest_version' => $latestVersion,
        'laravel_requirement' => $laravelRequirement
    ];
}

$totalPackages = count($packages);
$currentPackage = 0;

foreach ($packages as $package => $currentVersion) {
    $currentPackage++;
    echo "[$currentPackage/$totalPackages] Проверяем {$package}...\n";
    
    $result = checkPackageCompatibility($package);
    
    echo "  Текущая версия: {$currentVersion}\n";
    echo "  Последняя версия: {$result['latest_version']}\n";
    echo "  Laravel requirement: {$result['laravel_requirement']}\n";
    
    switch ($result['status']) {
        case 'compatible':
            echo "  ✅ {$result['message']}\n";
            $results['compatible'][] = $package;
            break;
        case 'needs_update':
            echo "  ⚠️  {$result['message']}\n";
            $results['needs_update'][] = $package;
            break;
        case 'unknown':
            echo "  ❓ {$result['message']}\n";
            $results['unknown'][] = $package;
            break;
        case 'error':
            echo "  ❌ {$result['message']}\n";
            $results['error'][] = $package;
            break;
    }
    
    echo "\n";
    
    // Небольшая пауза чтобы не перегружать API
    usleep(500000); // 0.5 секунды
}

// Выводим сводку
echo str_repeat("=", 80) . "\n";
echo "📊 СВОДКА РЕЗУЛЬТАТОВ\n";
echo str_repeat("=", 80) . "\n\n";

echo "✅ СОВМЕСТИМЫЕ ПАКЕТЫ (" . count($results['compatible']) . "):\n";
foreach ($results['compatible'] as $package) {
    echo "   • {$package}\n";
}
echo "\n";

echo "⚠️  ТРЕБУЮТ ОБНОВЛЕНИЯ (" . count($results['needs_update']) . "):\n";
foreach ($results['needs_update'] as $package) {
    echo "   • {$package}\n";
}
echo "\n";

echo "❓ НЕОПРЕДЕЛЕННЫЙ СТАТУС (" . count($results['unknown']) . "):\n";
foreach ($results['unknown'] as $package) {
    echo "   • {$package}\n";
}
echo "\n";

echo "❌ ОШИБКИ ПРОВЕРКИ (" . count($results['error']) . "):\n";
foreach ($results['error'] as $package) {
    echo "   • {$package}\n";
}
echo "\n";

// Рекомендации
echo str_repeat("-", 80) . "\n";
echo "📝 РЕКОМЕНДАЦИИ:\n";
echo str_repeat("-", 80) . "\n";

if (!empty($results['needs_update'])) {
    echo "1. Обновите следующие пакеты до версий с поддержкой Laravel 11/12:\n";
    foreach ($results['needs_update'] as $package) {
        echo "   composer require {$package}:^latest\n";
    }
    echo "\n";
}

if (!empty($results['unknown']) || !empty($results['error'])) {
    echo "2. Проверьте вручную следующие пакеты:\n";
    foreach (array_merge($results['unknown'], $results['error']) as $package) {
        echo "   https://packagist.org/packages/{$package}\n";
    }
    echo "\n";
}

echo "3. Ваши форки требуют создания веток laravel-11-12-support:\n";
$yourForks = [
    'babenkoivan/scout-elasticsearch-driver',
    'bumbummen99/shoppingcart', 
    'arturishe21/laravel-liqpay',
    'maksa988/laravel-wayforpay',
    'usamamuneerchaudhary/commentify'
];

foreach ($yourForks as $fork) {
    echo "   • {$fork}\n";
}

echo "\n4. Основные изменения для Laravel 11/12:\n";
echo "   • PHP: ^8.2\n";
echo "   • Laravel Framework: ^11.0|^12.0\n";
echo "   • Predis: ^2.0\n";
echo "   • Возможно PHPUnit: ^10.0\n";
echo "   • Возможно Collision: ^8.0\n";

echo "\n" . str_repeat("=", 80) . "\n";
echo "🎯 Готово! Проверьте результаты и обновите пакеты по необходимости.\n";
echo str_repeat("=", 80) . "\n";