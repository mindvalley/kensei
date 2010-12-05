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
?>

<?php
/**
* Lib should never be called directly.
* This class should be inherited by a child class
* functions in the child class should have a param parameter
* an example is:
class Indigo extends Lib {
    function example($param=array())
    {
        var_dump($param);
    }
}
* the code above will return the mapping of tag's parameter and its value
* {{ prodigy_example test1="apple" test2 = "apple2" }}
* will cause $param to return array("test1"=>"apple", "test2" => "apple2")
*
* There are some reserved word for $param keys,
* BLOCK is for getting contents within the tags
* _PATH is where the tag is called, note that this is different from $_SERVER path
*          its simillar to the __FILE__ command,
*          {{ page_include = "/example/test" }}
*          tags in the include will get the real path which is "/example/test"
*/
abstract class Lib {
    var $config;
    var $requestParams;
    var $notTranslatable;
    function __construct()
    {
        $requestParam = null;
        $this->config = Zend_Registry::get('config');
        $this->notTranslatable = array();
    }

    function __call($name,$params)
    {
        $params = $params[0];
        if (strlen($name) >= 7 &&substr_compare($name,'request',0,7,false) == 0){
            return '/404';
        }
        return '<div class="rp_warning"> <h1>ERROR TAG {{ '.$name.' }} DOES NOT EXIST!</h1></div>';
    }
    
    /**
     * Creates run strings with php codes in it. and return a string
     */
    protected function _runPhpCode($code,$passVariables = array())
    {
        foreach ($passVariables as $key => $data){
                $$key = $data;
        }
        ob_start();
        eval('?>'.$code.'<?php;');
        $data = ob_get_contents();
        ob_end_clean();
        return $data;
    }

    /**
     * helper function to create the target location for forms actions.
     */
    protected function _formAction($formName,$class='')
    {
        if (empty($class)){
            $class = get_class($this);
            $class = strtolower($class);
        }
        return $this->config->site->base_path.$this->config->form->action->capture . '/' . $class . '/' . $formName;
    }

    /**
     * helper function to make code neater,
     * also checks if there is a default overide in config
     * "isset($params['something']) ? $something = trim($params['something']) : $something = 'defaultvalue';"
     */
    protected static function _getParam($params,$key,$default='',$prefix='',$postfix='')
    {
        $param = (string) $params[$key];

        if (empty($param) && $param != '0'){
            $trace=debug_backtrace();
            array_shift($trace);
            $caller=array_shift($trace);
            $class = $caller['class'];
            while($class = get_parent_class($class)) { $classes[] = $class; }
            if (!empty($classes) && in_array('Lib',$classes)){
                $config = Zend_Registry::get('config');
                if (isset($config->variableDefaults->$caller['class']->$caller['function']->$key)){
                    $param = $config->variableDefaults->$caller['class']->$caller['function']->$key;
                }else{
                    $param = $default;
                }
            }else{
                $param = $default;
            }
        }else{
            $param = $prefix.$param.$postfix;
        }
        return $param;
    }

    protected function _getUrlParam()
    {
        $url = Wrapper::getUri(true);
        list($url,$dummy) = explode('?',$url,2);
        $url = str_replace('http://','',$url);
        $url = ltrim($url,'/');
        $urlParts=array();
        if (strstr($url,'/') !==false){
            $parts = explode('/',$url);
            for($i=1;$i < count($parts);$i++){
                $urlParts[$parts[$i-1]] = $parts[$i];
            }
        }
        return $urlParts;
    }

    protected static function _textile($string)
    {
        if (empty($string)){
            return '';
        }
        $config = Zend_Registry::get('config');
        if (!Zend_Registry::isRegistered('textile')){
            $textile = new Textile();
            Zend_Registry::set('textile', $textile);
        }else{
            $textile = Zend_Registry::get('textile');
        }
        
        if ($config->textile->tagsCaching){
            $identifier = hash('md4',$string);
            $cacher = Wrapper::getCache($identifier,2592000, BASE_DIR.'/cache/');
            if (is_null($cacher)){
                $content = $textile->TextileThis($string);
                $cleanedHtml = $content;
                $contentLength = strlen($content);
                if ($contentLength >= 3 && substr_compare($content,'<p>',0,3,false) == 0){
                    $cleanedHtml = substr($content,3);
                    
                    $contentLength = strlen($cleanedHtml);
                    if ($contentLength >= 4 && substr_compare($cleanedHtml,'</p>',$contentLength-4,4,false) == 0){
                        $cleanedHtml = substr($cleanedHtml,0,$contentLength-4);
                    }
                }            
                Wrapper::setCache($identifier,$cleanedHtml,2592000, BASE_DIR.'/cache/');
                return $cleanedHtml;
            }else{
                return $cacher;
            }
        }else{
            $content = $textile->TextileThis($string);
            $cleanedHtml = $content;
            $contentLength = strlen($content);
            if ($contentLength >= 3 && substr_compare($content,'<p>',0,3,false) == 0){
                $cleanedHtml = substr($content,3);
                
                $contentLength = strlen($cleanedHtml);
                if ($contentLength >= 4 && substr_compare($cleanedHtml,'</p>',$contentLength-4,4,false) == 0){
                    $cleanedHtml = substr($cleanedHtml,0,$contentLength-4);
                }
            }
            return $cleanedHtml;
        }
        
        
    }

    /**
    * Convert underscore structure param to array
    * <key><ilterator>_<name>
    * @var string
    */
    protected function _paramToArray($params,$nameHead)
    {
        $nameLength = strlen($nameHead);
        $items = array();
        foreach ($params as $key=>$value){
            if(strlen($key) > $nameLength && substr_compare($key,$nameHead,0,$nameLength) == 0){
                $headlessKey = substr($key,$nameLength);
                $firstSeparator = strpos($headlessKey,'_');
                $itemIndex = substr($headlessKey,0,$firstSeparator);
                $name = substr($headlessKey,$firstSeparator+1);
                $items[$itemIndex][$name] = $value;
            }
        }
        return $items;
    }

    /**
    * Renders php files and generate pure html.
    * @var string
    * @var array
    */
    protected function _getHtmlTemplate($fileName,$passVariables = array(),$useClassPath = true)
    {
        if ($useClassPath){
            $partialDir = 'partials/' .get_class($this) . '/';
        }else{
            $partialDir = 'partials/';
        }
        $htmlFile = USER_LAYOUTS_DIR . $partialDir . $fileName;
        if (!file_exists($htmlFile)){
            $htmlFile = LAYOUTS_DIR . $partialDir . $fileName;
            if (!file_exists($htmlFile)){
                return '';
            }
        }

        if (count($passVariables) > 0) {
            foreach ($passVariables as $key => $data){
                    $$key = $data;
            }
        }
        
        ob_start();
        include($htmlFile);
        $data = ob_get_contents();
        ob_end_clean();
        return $data;
    }

    protected static function _injectScope($scope,$data,$forceOverride=false)
    {
        $parser = Zend_Registry::get('parser', $parser );
        $parser->injectScope(array($scope=>$data),$forceOverride);
    }
    
    protected static function _getScope($scope)
    {
        $parser = Zend_Registry::get('parser', $parser );
        return $parser->getScope($scope);
    }
    
    public abstract function scopeOf($functionName);
/* {
        switch ($functionName){
            default:
                return '';
        }
    }*/
    
}
