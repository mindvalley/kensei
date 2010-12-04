<?php


/**
 * @see Zend_Validate_Hostname_Interface
 */
require_once 'Zend/Validate/Hostname/Interface.php';

class Zend_Validate_Hostname_Dev implements Zend_Validate_Hostname_Interface
{
    static function getCharacters()
    {
        return '_';
    }

}
