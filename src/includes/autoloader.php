<?php

namespace Soisy\Includes;

spl_autoload_register(function ($wantedClass) {
    if (!isSoisyClass($wantedClass)) {
        return;
    }

    $filesToLoad = [
        __DIR__ . '/class-helper.php',
        __DIR__ . '/class-settings.php',
        __DIR__ . '/../SoisyClient.php',
    ];

    foreach ($filesToLoad as $filename) {
        if (!file_exists($filename)) {
            wp_die(sprintf('File %s not found', $filename));
        }

        include_once($filename);
    }

    if (!class_exists($wantedClass)) {
        wp_die(sprintf('Class %s not found', $wantedClass));
    }
});

function isSoisyClass($wantedClass)
{
    return stripos($wantedClass, 'soisy') !== false;
}