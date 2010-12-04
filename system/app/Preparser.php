<?php

abstract class Preparser
{
    var $config;

    function __construct()
    {
        $this->config = Zend_Registry::get('config');
    }
    abstract function init();
}
