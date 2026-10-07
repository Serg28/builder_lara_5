<?php

/**
 * Laravel 11 & 12 Compatibility Checker for Builder CMS
 * 
 * This script checks if your environment is ready for Laravel 11 & 12
 */

echo "🔍 Checking Laravel 11 & 12 compatibility...\n\n";

$errors = [];
$warnings = [];
$success = [];

// Check PHP version
echo "📋 Checking PHP version...\n";
$phpVersion = PHP_VERSION;
if (version_compare($phpVersion, '8.2.0', '>=')) {
    $success[] = "✅ PHP version: {$phpVersion} (Compatible)";
} else {
    $errors[] = "❌ PHP version: {$phpVersion} (Requires 8.2+)";
}

// Check PHP extensions
echo "🔧 Checking PHP extensions...\n";
$requiredExtensions = [
    'mbstring',
    'openssl',
    'pdo',
    'tokenizer',
    'xml',
    'ctype',
    'json',
    'bcmath',
    'fileinfo',
    'gd'
];

foreach ($requiredExtensions as $extension) {
    if (extension_loaded($extension)) {
        $success[] = "✅ Extension {$extension}: Available";
    } else {
        $errors[] = "❌ Extension {$extension}: Missing";
    }
}

// Check optional extensions
$optionalExtensions = [
    'imagick' => 'For advanced image processing',
    'redis' => 'For Redis caching',
    'memcached' => 'For Memcached support'
];

foreach ($optionalExtensions as $extension => $description) {
    if (extension_loaded($extension)) {
        $success[] = "✅ Extension {$extension}: Available ({$description})";
    } else {
        $warnings[] = "⚠️  Extension {$extension}: Missing ({$description})";
    }
}

// Check Composer
echo "📦 Checking Composer...\n";
$composerVersion = null;
exec('composer --version 2>/dev/null', $output, $returnCode);
if ($returnCode === 0 && !empty($output)) {
    $composerVersion = $output[0];
    $success[] = "✅ Composer: Available ({$composerVersion})";
} else {
    $errors[] = "❌ Composer: Not found or not accessible";
}

// Check if we're in a Laravel project
echo "🏗️  Checking Laravel project...\n";
if (file_exists('composer.json')) {
    $composerJson = json_decode(file_get_contents('composer.json'), true);
    
    if (isset($composerJson['require']['laravel/framework'])) {
        $laravelVersion = $composerJson['require']['laravel/framework'];
        $success[] = "✅ Laravel project detected (Version constraint: {$laravelVersion})";
        
        // Check if it's compatible with Laravel 11 & 12
        if (strpos($laravelVersion, '^11.') !== false || 
            strpos($laravelVersion, '^12.') !== false ||
            strpos($laravelVersion, '^11.0|^12.0') !== false) {
            $success[] = "✅ Laravel version constraint is compatible with 11 & 12";
        } else {
            $warnings[] = "⚠️  Laravel version constraint may need updating for 11 & 12 support";
        }
    } else {
        $warnings[] = "⚠️  This doesn't appear to be a Laravel project";
    }
} else {
    $warnings[] = "⚠️  composer.json not found - run this from your Laravel project root";
}

// Check current Builder CMS version
if (file_exists('vendor/vis/builder_lara_5/composer.json')) {
    $builderComposer = json_decode(file_get_contents('vendor/vis/builder_lara_5/composer.json'), true);
    if (isset($builderComposer['version'])) {
        $builderVersion = $builderComposer['version'];
        $success[] = "✅ Builder CMS detected (Version: {$builderVersion})";
    }
} else {
    $warnings[] = "⚠️  Builder CMS not found in vendor directory";
}

// Display results
echo "\n" . str_repeat("=", 60) . "\n";
echo "📊 COMPATIBILITY CHECK RESULTS\n";
echo str_repeat("=", 60) . "\n\n";

if (!empty($success)) {
    echo "✅ SUCCESS:\n";
    foreach ($success as $item) {
        echo "   {$item}\n";
    }
    echo "\n";
}

if (!empty($warnings)) {
    echo "⚠️  WARNINGS:\n";
    foreach ($warnings as $item) {
        echo "   {$item}\n";
    }
    echo "\n";
}

if (!empty($errors)) {
    echo "❌ ERRORS:\n";
    foreach ($errors as $item) {
        echo "   {$item}\n";
    }
    echo "\n";
}

// Final recommendation
echo str_repeat("-", 60) . "\n";
if (empty($errors)) {
    if (empty($warnings)) {
        echo "🎉 RESULT: Your environment is fully compatible with Laravel 11 & 12!\n";
        echo "You can proceed with the upgrade.\n";
    } else {
        echo "✅ RESULT: Your environment is compatible with Laravel 11 & 12.\n";
        echo "Please review the warnings above.\n";
    }
    echo "\nNext steps:\n";
    echo "1. Run: ./upgrade-to-laravel-11-12.sh\n";
    echo "2. Review: LARAVEL_11_12_COMPATIBILITY.md\n";
} else {
    echo "❌ RESULT: Your environment needs fixes before upgrading.\n";
    echo "Please resolve the errors above before proceeding.\n";
}

echo str_repeat("-", 60) . "\n";
echo "📚 For more information, see: LARAVEL_11_12_COMPATIBILITY.md\n";