<?php
class Loader extends Zend_Loader
{
    public static function autoload($class)
    {
        try {
            // load it like a stack
            parent::loadClass($class,array(CORE_SERVICE_DIR,
                            APP_DIR,
                            LIB_DIR,
                            VENDOR_DIR));
            return $class;
        } catch (Exception $e) {
            return false;
        }
    }
}
