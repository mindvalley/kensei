<?php
abstract class Api {
    var $config;
    function __construct()
    {
        $this->config = Zend_Registry::get('config');
    }

}
