<?php
/**
 * Dynamically loads the class attempting to be instantiated elsewhere in the
 * plugin.
 *
 * @package  Soisy
 */

spl_autoload_register('soisy_payment_autoload');

/**
 * The namespaces in this plugin map to the paths in the directory structure.
 *4
 * @param string $wantedClass The fully-qualified name of the class to load.
 */
function soisy_payment_autoload($wantedClass)
{

    if (!isSoisyClass($wantedClass)) {
        return;
    }

    $filesToLoad = [
        __DIR__ . '/class-helper.php',
        __DIR__ . '/class-settings.php',
        __DIR__ . '/product/class-view.php',
        __DIR__ . '/checkout/cart/class-view.php',
        __DIR__ . '/checkout/class-selectinstalments.php',
        __DIR__ . '/../soisy-lib-php/src/Soisy/Loan/Quotes.php',
        __DIR__ . '/../soisy-lib-php/src/Soisy/Order/Token.php',
        __DIR__ . '/../soisy-lib-php/src/Soisy/Client.php',
        __DIR__ . '/../soisy-lib-php/src/Soisy/Exception.php',
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
}

function isSoisyClass($wantedClass)
{
    return stripos($wantedClass, 'soisy') !== false;
}