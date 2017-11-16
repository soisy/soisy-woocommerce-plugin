<?php
/**
 * Dynamically loads the class attempting to be instantiated elsewhere in the
 * plugin.
 *
 * @category Bitbull
 * @package  Bitbull_Soisy
 * @author   Martins Saukums <martins.saukums@bitbull.it>
 */

spl_autoload_register('soisy_payment_autoload');

/**
 * The namespaces in this plugin map to the paths in the directory structure.
 *
 * @param string $class_name The fully-qualified name of the class to load.
 */
function soisy_payment_autoload($class_name)
{

    // If the specified $class_name does not include our namespace, duck out.
    if (false === strpos($class_name, 'Bitbull_Soisy')) {
        return;
    }

    // Split the class name into an array to read the namespace and class.
    $file_parts = explode('\\', $class_name);
    $file_name = '';
    // Do a reverse loop through $file_parts to build the path to the file.
    $namespace = '';
    for ($i = count($file_parts) - 1; $i > 0; $i--) {

        // Read the current component of the file part.
        $current = strtolower($file_parts[$i]);
        $current = str_ireplace('_', '-', $current);

        // If we're at the first entry, then we're at the filename.
        if (count($file_parts) - 1 === $i) {

            /* If 'interface' is contained in the parts of the file name, then
             * define the $file_name differently so that it's properly loaded.
             * Otherwise, just set the $file_name equal to that of the class
             * filename structure.
             */
            if (strpos(strtolower($file_parts[count($file_parts) - 1]), 'interface')) {

                // Grab the name of the interface from its qualified name.
                $interface_name = explode('_', $file_parts[count($file_parts) - 1]);
                $interface_name = $interface_name[0];

                $file_name = "interface-$interface_name.php";

            } else {
                $file_name = "class-$current.php";
            }
        } else {
            $namespace = '/' . $current . $namespace;
        }
    }

    // Now build a path to the file using mapping to the file location.
    $filepath = trailingslashit(dirname(dirname(__FILE__)) . $namespace);

    if ($file_name) {
        $filepath .= $file_name;
    } else {
        // Include soisy php lib autoload
        $filepath .= 'soisy-lib-php' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR;
        $className = ltrim($class_name, "\/");

        $file_name = str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';
        $filepath .= $file_name;
    }

    // If the file exists in the specified path, then include it.
    if (file_exists($filepath)) {
        include_once($filepath);
    } else {
        wp_die(
            esc_html("The file attempting to be loaded at $filepath does not exist.")
        );
    }
}
