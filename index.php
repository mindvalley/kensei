<?php
$startTime = microtime(true) ;
// Constant Definition
define('BASE_DIR', dirname(__FILE__) . '/');
define('MEDIA_DIR', BASE_DIR . 'media/');
define('CONTENT_DIR', BASE_DIR . 'content/');
define('SYSTEM_DIR', BASE_DIR . 'system/');

define('APP_DIR', SYSTEM_DIR . 'app/');
define('CONFIG_DIR', SYSTEM_DIR  . 'config/');
define('MODULES_DIR', SYSTEM_DIR . 'modules/');
define('PLUGINS_DIR', SYSTEM_DIR . 'plugins/');
define('VENDOR_DIR', SYSTEM_DIR . 'vendor/');
define('LAYOUTS_DIR', SYSTEM_DIR . 'layouts/');
define('USER_LAYOUTS_DIR', BASE_DIR . 'layouts/');
define('TMP_DIR',sys_get_temp_dir());

set_include_path( APP_DIR . PATH_SEPARATOR . get_include_path());
set_include_path( MODULES_DIR . PATH_SEPARATOR . get_include_path());
set_include_path( PLUGINS_DIR . PATH_SEPARATOR . get_include_path());
set_include_path( VENDOR_DIR . PATH_SEPARATOR . get_include_path());
set_include_path( LAYOUTS_DIR . PATH_SEPARATOR . get_include_path());

// Core Application files.
require_once 'Zend/Loader/Autoloader.php';
$loader = Zend_Loader_Autoloader::getInstance();
$loader->setFallbackAutoloader(true);

$generator = new Generator();

try{
    $generator->start();
}catch (exception $e){
    echo '<html><head><title>Error</title></head><body>An error has occured, <a href="javascript:location.reload(true);">click here to try again</a><br />'.$message.'</body></html>';
}
echo "<!-- Page Generated in: ".( round(microtime(true) - ($startTime) ,6) * 1000)." ms on ".php_uname('n')." -->" ;
exit();
