<?php
/**
*  Page generation engine
*/
class Generator {
    var $config;
    var $fileExtension;
    var $fileExtensionLen;
    var $preparserScopes;
    /**
    * Constructs the Generator class.
    *
    */
    function __construct()
    {
        if (!Zend_Registry::isRegistered('config')){
            $this->config  = new Config();
            Zend_Registry::set('config', $this->config );
        }else{
            $this->config = Zend_Registry::get('config');
        }

        $this->fileExtension = $this->config->application->contentFile->extension;
        $this->fileExtensionLen = (integer) $this->config->application->contentFile->extensionLength;
        if (!empty($this->config->application->timezone)){
            date_default_timezone_set($this->config->application->timezone);
        }
        Tag::init();
        if ($this->config->application->hideError){
            error_reporting(0);
        }else{
            error_reporting(E_ERROR|
                            E_WARNING|
                            E_PARSE|
                            E_CORE_ERROR|
                            E_CORE_WARNING|
                            E_COMPILE_ERROR|
                            E_COMPILE_WARNING|
                            E_USER_ERROR|
                            E_USER_WARNING|
                            E_USER_NOTICE|
                            E_STRICT|
                            E_RECOVERABLE_ERROR);
        }
        $this->loadPreparser();
    }

    /**
    * Starts the application.
    *
    */
    public function start(){
        $router = new Router(Wrapper::getUri(false,false));
        $path = $router->requestPath;
        $parserClass = $this->config->application->parserClass;
        $valid = $router->valid();
        $formCapture = $router->isFormCapture();

        if ($valid && !$formCapture){
            $fileLocation = $router->realPath;
            $parser = new $parserClass($fileLocation,$path);
        }elseif ($formCapture){
            $formResult = $router->processFormCapture();
            if ($formResult !== false){
                $parser = new $parserClass($formResult,$path);
            }else{
                //return;
            }
        }else{
            $fileLocation = $router->getPreset('404');
            $parser = new $parserClass($fileLocation,$path);
        }
        if (!Zend_Registry::isRegistered('parser')){
            Zend_Registry::set('parser', $parser );
        }else{
            $parser = Zend_Registry::get('parser');
        }
        if (isset($parser)){
            foreach ($this->preparserScopes as $scopeArray){
                if (is_array($scopeArray) && count($scopeArray) > 0){
                    $parser->injectScope($scopeArray);
                }
            }
            echo $parser->render($this->config->application->generateStatic);
        }
    }

    /**
    * Process  Stuff that needs to be processed before the parser.
    *
    */
    function loadPreparser()
    {
        $this->preparserScopes = array();
        $directory = dir( MODULES_DIR . 'Preparser');
        for ($i = 0;$i<2;$i++){
            while (($currentFile = $directory->read()) !== false){
                if (substr($currentFile,strlen($currentFile)-4) == '.php'){
                    $className = 'Preparser_'.substr($currentFile,0,strlen($currentFile)-4);
                    if (get_parent_class($className) == 'Preparser'){
                        $preparser = new $className();
                        array_push($this->preparserScopes,$preparser->init());
                    }
                }
            }
            $directory = dir( PLUGINS_DIR . 'Preparser');
        }
    }


    function generateHtaccess (){
        $captureLocation = $this->config->form->action->capture;
        return '
RewriteEngine on
RewriteRule ^'.$captureLocation.'/(.*)$ '.$captureLocation.'.php

RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.+)/$ http://%{HTTP_HOST}/$1 [R=301,QSA,L]

RewriteCond %{REQUEST_URI} !(\.[^./]+)$
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule (.+) /$1.php [L]

ErrorDocument 404 /404
';

    }
}
