<?php
/***
This file is part of Tensai.

Tensai is free software: you can redistribute it and/or modify
it under the terms of the Affero GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

Tensai is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
Affero GNU General Public License for more details.

You should have received a copy of the Affero GNU General Public License
along with Tensai.  If not, see <http://www.gnu.org/licenses/>.
***/


class Router
{
    var $requestPath;
    var $realPath;
    /**
    *  Constructs the router and set the path.
    * @var string
    */
    function __construct($requestPath = '')
    {
        $requestPath = trim($requestPath,'/');
        @list($requestPath,$queryStr) = explode('?',$requestPath,2);
        $this->requestPath = $requestPath;
        $config = Zend_Registry::get('config');

        if($config->site->base_path != '/'){
            $this->requestPath = trim(str_replace(trim($config->site->base_path,'/'),'',$this->requestPath),'/');
        }
        $this->setRealPath();
    }

    private function _getPageAlias($path)
    {
        $config = Zend_Registry::get('config');
        if (!empty($config->page->$path)){
            return $config->page->$path;
        }
        foreach ($config->page as $alias=>$aliasPath){
            if (strlen($alias)>0 && strlen($path)>0 && substr($alias,strlen($alias)-1) == '*'){
                $wildcardAlias = substr($alias,0,strlen($alias)-1);
                if (strlen($wildcardAlias) < strlen($path) &&
                        substr_compare($path,$wildcardAlias,0,strlen($wildcardAlias),false) == 0){
                    return $aliasPath;
                }
            }
        }
        return $path;
    }

    /**
    *  Sets the real path.
    * if $path not specified, will use the path specified in the constructor
    * @var string
    */
    private function setRealPath($path=null){
        if (is_null($path)){
            $path = $this->requestPath;
        }
        $config = Zend_Registry::get('config');
        $path = $this->_getPageAlias($path);
        if (empty($path)){
            $this->realPath = CONTENT_DIR .'index'.$config->application->contentFile->extension;
        }elseif (file_exists(CONTENT_DIR . $path.$config->application->contentFile->extension)){
            $this->realPath = CONTENT_DIR . $path.$config->application->contentFile->extension;
        }elseif (file_exists(CONTENT_DIR . $path.'/index'.$config->application->contentFile->extension)){
            $this->realPath = CONTENT_DIR . $path.'/index'.$config->application->contentFile->extension;
        }
    }

    /**
    * crawl trough all logical path and return a logical paths array.
    * @var string
    */
    public static function getAllLogicalPath($directory=null,$parentDir='/'){
        if (is_null($directory)){
            $directory = dir( CONTENT_DIR );
        }
        $config = Zend_Registry::get('config');
        $currentPath = $directory->path;
        $dirStructure = array();
        while (($currentFile = $directory->read()) !== false){
            if (substr_compare($currentFile,'~',strlen($currentFile) - 1) != 0){
                if ( substr_compare($currentFile,'.',0,1) != 0){
                    $thePath = $currentPath.'/'.$currentFile;
                    if (is_dir($thePath)){
                        $thePath = substr($thePath,strlen(CONTENT_DIR));
                        //$dirStructure[$thePath] = self::getAllLogicalPath(dir(CONTENT_DIR . $thePath),$parentDir.$thePath);
                        $dirStructure = array_merge_recursive($dirStructure,self::getAllLogicalPath(dir(CONTENT_DIR . $thePath),$parentDir.$thePath));
                    }else{
                        $thePath = substr($thePath,strlen(CONTENT_DIR));
                        if ($thePath == '/index'.$config->application->contentFile->extension){
                            $dirStructure[$thePath] = '/';
                        }else{
                            $dirStructure[$thePath] = substr($thePath,0, strlen($thePath) - $config->application->contentFile->extensionLength);
                        }
                    }
                }
            }
        }
        return $dirStructure;
    }

    /**
    * treats the current path as a post/request request
    */
    function processFormCapture()
    {
        $config = Zend_Registry::get('config');
        $capture = $config->form->action->capture;
        $path = $this->requestPath;
        $path = trim($this->_getPageAlias($path),'/');
        $classModule = substr($path,strlen($capture));
        $classModule = trim($classModule,'/');

        list($className,$functionName,$param) = explode('/',$classModule,3);
        $className = ucfirst(strtolower($className));
        $class = $className;
        // must be a child of Lib class for security reasons.
        while($class = get_parent_class($class)) { $classes[] = $class; }
        if (empty($classes) || !in_array('Lib',$classes)){
            return $this->getPreset('404');
        }
        $functionName= 'request'.$functionName;
        $classMethods = get_class_methods($className);
        foreach ($classMethods as $method){
            if (strtolower($functionName) == strtolower($method)){
                $functionName = $method;
                break;
            }
        }


        $classObject = new $className();

        if (method_exists($classObject,$functionName) == false){
            return $this->getPreset('404');
        }

        if (!empty($param)){
            if (strstr($param,'/') !==false){
                $params = explode('/',$param);
                for($i=1;$i < count($params);$i++){
                    $classObject->requestParams[$params[$i-1]] = $params[$i];
                }
            }else{
                $classObject->requestParams[$param] ='';
            }
        }
        $redirect = $classObject->$functionName();

        if (!empty($redirect) && $redirect !== false){
            header("Location: $redirect");
            die();
        }elseif ($redirect !== false){
            header("Location: /");
            die();
        }
        return false;
    }

    /**
    * Checks the validity of the path.
    */
    function valid()
    {
        return file_exists($this->realPath);
    }

    /**
    * Checks if the path is a form capture path
    */
    function isFormCapture()
    {
        $config = Zend_Registry::get('config');
        $path = $this->requestPath;
        $path = $this->_getPageAlias($path);
        $path = trim($path,'/');
        $formActionCaptureLength = strlen($config->form->action->capture);
        if ($formActionCaptureLength < strlen($path)){
            return (substr_compare($path.'/',$config->form->action->capture.'/',0,$formActionCaptureLength+1) == 0);
        }else{
            return false;
        }
    }

    /**
    * Gets preset paths.
    */
    function getPreset($mapper){
        $config = Zend_Registry::get('config');
        switch ($mapper){
            case "index":
                $this->setRealPath('/');
            default:
                $this->setRealPath($config->site->error404);
            break;
        }
        return $this->realPath;
    }
}
