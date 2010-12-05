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


class Page extends Lib{
    /** Standard Lib Functions START **/
    function __construct()
    {
        parent::__construct();
    }

    function scopeOf($functionName)
    {
        switch ($functionName){
            case 'afterHead':
                return 'afterHead';
                break;
            case 'title':
                return 'title';
                break;
            case 'sideBar':
                return 'sidebar';
                break;
            case 'beforeFooter':
                return 'beforeFooter';
                break;
            case 'footerExtra':
                return 'footerExtra';
                break;
            case 'endOfPage':
                return 'endOfPage';
                break;
            case 'topOfPage':
                return 'topOfPage';
                break;
            case 'javascript':
                return 'javascript';
                break;
            case 'css':
                return 'style';
                break;
            case 'layout':
                return 'layout';
                break;
            case 'headerExtras':
                return 'headerExtras';
                break;
            case 'header':
                return 'header';
                break;
            case 'redirect':
                return 'header';
                break;
            case 'message':
                return 'precontent';
                break;
            case 'meta':
                return 'metaTag';
                break;
            case 'description':
                return 'description';
                break;
            case 'keywords':
                return 'keywords';
                break;
            default:
                return '';
        }
    }
    /** Standard Lib Functions STOP **/

    /** Form Handlers START **/
    function requestCompileJavascript()
    {
        $currentPath = Wrapper::getUri();
        $_GET['javascript'] = escapeshellarg("");

        $id = "compile";
        if (Zend_Registry::isRegistered('javascript')) {
            $jsList = Zend_Registry::get('javascript');
            $js = implode('|',$jsList);
        }else{
            $jsList = '';
            $js = '';
        }
        $id = md5($id.$js);


        // obsolete but needed for ie6, use application/javascript when ie6 dies
        header('Content-type: text/javascript');
        if (!file_exists(TMP_DIR . '/' . $this->config->site->domain)){
            mkdir(TMP_DIR . '/' . $this->config->site->domain);
        }
        $cache = Zend_Cache::factory('Core',
                        'File',
                        array('lifetime' => 900
                                , 'automatic_serialization' => true,
                                'cache_id_prefix' => 'jsCache'),
                        array('cache_dir' => TMP_DIR . '/' . $this->config->site->domain));
        if (!$cache->test($id)){
            $content = '';
            foreach ($this->config->layout->js as $defaultJs){
                if (file_exists(MEDIA_DIR . 'javascripts/' . $defaultJs)){
                    $content .= file_get_contents(MEDIA_DIR . 'javascripts/' . $defaultJs);
                }
            }
            foreach ($jsList as $jsName){
                if (file_exists(escapeshellarg(MEDIA_DIR . 'javascripts/' . $defaultJs))){
                    $content .= file_get_contents(escapeshellarg(MEDIA_DIR . 'javascripts/' . $jsName));
                }
            }
            if ($this->config->application->gzipJavascript){
                //$content = gzencode($contents, 9, $gzip ? FORCE_GZIP : FORCE_DEFLATE);
            }
            $cache->save($content,$id);
            echo $content;
        }else{
            echo $cache->load($id);
        }
        die();
        return false;
    }
    /** Form Handlers STOP **/

    /** Tags Methods START **/

    function error404($params=array()){
        $pageScope = array();
        $path = trim(Wrapper::getUri(),'/');
        if (empty($path)){
            $pageScope['bodyAttrib'] = 'id="index" class="e404"';
        }else{
            if (strstr($path,'/')!== false){
                list($id,$class) = explode('/',$path,2);
                if (!empty($this->config->site->bodyAttributeNumericPrefix)){
                    $classAry = explode('/',$class);
                    $class = '';
                    foreach ($classAry as $currClass){
                        if (is_numeric($currClass)){
                            $currClass = $this->config->site->bodyAttributeNumericPrefix . $currClass;
                        }
                        $class .= $currClass . ' ';
                    }
                    $class = trim($class);
                }else{
                    $class = str_replace('/',' ',$class);
                }
                $pageScope['bodyAttrib'] = 'id="'.$id.'" class="'.$class.' e404"';
            }else{
                $pageScope['bodyAttrib'] = 'id="'.$path.'" class="e404"';
            }
        }
        $this->header(array('value'=>'HTTP/1.0 404 Not Found'));
        $parser = Zend_Registry::get('parser', $parser );
        $parser->injectScope($pageScope);
    }

    function bodyTagAttribute($params){
        $path = trim(Wrapper::getUri(),'/');
        list($id,$class) = explode('/',$path,2);
        if (!empty($this->config->site->bodyAttributeNumericPrefix)){
            $classAry = explode('/',$class);
            $class = '';
            foreach ($classAry as $currClass){
                if (is_numeric($currClass)){
                    $currClass = $this->config->site->bodyAttributeNumericPrefix . $currClass;
                }
                $class .= $currClass . ' ';
            }
            $class = trim($class);
        }else{
            $class = str_replace('/',' ',$class);
        }
        $id = self::_getParam($params,'id',$id);
        $class = self::_getParam($params,'class',$class);
        $this->_injectScope('bodyAttrib','id="'.$id.'" class="'.$class.'"',true);
    }

    function injectScope($params){
        $data = self::_getParam($params,'data','');
        if (!empty($params['BLOCK'])){
            $type = self::_getParam($params,'type','');
            if (empty($type)){
                $data = self::_getParam($params,'RAW','');
            }else{
                $data = self::_getParam($params,'BLOCK','');
            }
        }
        $this->_injectScope($params['scope'],$data,(!empty($params['force_override']) && $params['force_override']>0));
    }

    function afterHead($params=array())
    {
        if (!empty($params['BLOCK'])){
            $code =  $this->_runPhpCode($params['BLOCK']);
            return $code;
        }
    }

    function endOfPage($params=array())
    {
        if (!empty($params['BLOCK'])){
            $code =  $this->_runPhpCode($params['BLOCK']);
            return $code;
        }
    }
    function topOfPage($params=array())
    {
        if (!empty($params['BLOCK'])){
            $code =  $this->_runPhpCode($params['BLOCK']);
            return $code;
        }
    }
    function beforeFooter($params=array())
    {
        if (!empty($params['BLOCK'])){
            $code =  $this->_runPhpCode($params['BLOCK']);
            return $code;
        }
    }
    function footerExtra($params=array())
    {
        if (!empty($params['BLOCK'])){
            $code =  $this->_runPhpCode($params['BLOCK']);
            return $code;
        }
    }

    function hideExtras($params=array())
    {
        $this->_injectScope('showExtras',false,true);
    }

    function header($params=array())
    {
        header($params['value']);
    }

    function headerExtras ($params=array())
    {
        if (!empty($params['BLOCK'])){
            $code =  $this->_runPhpCode($params['BLOCK']);
            return $code;
        }
    }

    function includeFile($params=array())
    {
        $path = $params['file'];
        if (substr($path,$path - $this->config->application->contentFile->extensionLength) == $this->config->application->contentFile->extension){
            $path = substr($path,0,strlen($path)-$this->config->application->contentFile->extensionLength);
        }elseif (substr($path,$path - 8) == '.textile'){ //for deploying to html purpose
            $path = substr($path,0,strlen($path)-8);
        }
        $router = new Router($path);
        $parserClass = $this->config->application->parserClass;
        if ($router->valid() && !empty($path)){
            $fileLocation = $router->realPath;
        }else{
            $fileLocation = $router->getPreset('404');
        }
        $parser = new $parserClass($fileLocation ,$path);
        $parser->outputCode = $this->config->application->generateStatic;
        if (empty($parser->fileLocation)){
            return '';
        }
        $content = file_get_contents($parser->fileLocation);
        $tempContent = $content;
        $renderedContent = $parser->convertContent($tempContent);
        $renderedContent = str_replace("<p></p>","",$renderedContent);
        return $renderedContent;
    }

    function javascript($params=array())
    {
        if (empty($params['RAW'])){
            if (empty($params['compile'])){
                $file = $params['file'];
                return "<script src=\"$file\" type=\"text/javascript\"></script>\n";
            }else{
                Zend_Registry::set('javascript', substr($file,strlen($file)-3));
                return '';
            }
        }else{
            return $params['RAW'];
        }
    }

    function css($params=array())
    {
        $file = $params['file'];
        return '@import url("'.$file.'");';
    }

    function cssOverride($params=array())
    {
        $files = explode(',',$params['css_list']);
        $cssStyle = '';
        foreach ($files as $file){
            $cssStyle .= '@import url("'.$file.'");'."\n";
        }
        $this->_injectScope('style',$cssStyle,true);
    }

    function layout($params=array())
    {
        return $params['file'];
    }

    function keywords($params=array())
    {
        return $params['keywords'];
    }

    function description($params=array())
    {
        if (!Zend_Registry::isRegistered('H1Registered')){
            // so that H1 can not replace description as this tag have priorty
            Zend_Registry::set('H1Registered',true);
        }
        return $params['description'];
    }

    function printNested($params=array())
    {
        if (!empty($params['BLOCK'])){
            return $params['BLOCK'];
        }
    }

    function redirect($params=array())
    {
        header('HTTP/1.1 301 Moved Permanently');
        $withQueryString = self::_getParam($params,'with_query_string',0);
        if ($withQueryString != '0'){
            $redirectTo = $params['url'];
            if (count($_GET) >0){
                if (strstr($redirectTo,'?') === false){
                    $redirectTo .= '?';
                    foreach ($_GET as $key=>$data){
                         $redirectTo .= urlencode($key).'='.urlencode($data) . '&';
                    }
                }else{
                    $redirectTo .= '&';
                    foreach ($_GET as $key=>$data){
                         $redirectTo .= urlencode($key).'='.urlencode($data) . '&';
                    }
                }
                $redirectTo = rtrim($redirectTo,'& ');
            }
            header('Location: '.$redirectTo);
        }else{
            header('Location: '.$params['url']);
        }

        die();
    }

    function sideBar($params=array())
    {
        if (!empty($params['BLOCK'])){
            //$code =  trim($this->_runPhpCode($params['BLOCK']));
            $code = $params['BLOCK'];
            return $code;
        }
    }

    function ifScroll($params=array()){
        $percentStart = self::_getParam($params,'start_percent',0);
        $percentEnd = self::_getParam($params,'end_percent',100);
        $triggerFunction = self::_getParam($params,'trigger_javascript',0);


        $triggerStartFunction = self::_getParam($params,'trigger_start_javascript',0);
        $triggerEndFunction = self::_getParam($params,'trigger_end_javascript',0);

        return '
        <script type="text/javascript">
        var triggered = false;
        $(function(){
            $(window).scroll(function(){
                var percent = ($(window).height() + $(window).scrollTop()) / $(document).height() * 100;
                if (percent >= '.$percentStart.' && percent < '.$percentEnd.'){
                    '.$triggerStartFunction.';
                }else if (percent < '.$percentStart.' || percent >= '.$percentEnd.'){
                    '.$triggerEndFunction.';
                }
            });
        });
        </script>
        ';
    }

    function title($params=array())
    {
        if (!empty($params['title'])){
            return $params['title'];
        }elseif (!empty($params['dynamic'])){
            list($currentUri,$query) = explode('?',Wrapper::getUri());
            $path = trim($currentUri,'/');
            $pathPieces = explode('/',$path);
            foreach ($pathPieces as $key=>$piece){
                if (strstr($piece,'-') !== false){
                    $subPieces = explode('-',$piece);
                    $lastPiece = $subPieces[count($subPieces)-1];
                    if (!is_numeric($lastPiece) && strlen($lastPiece) < 2){
                        array_pop($subPieces);
                    }
                    $pathPieces[$key] = '';
                    foreach ($subPieces as $subKey=>$subPiece){
                        if ($subPiece != 'index'){
                            $pathPieces[$key] .= ucfirst($subPiece) . ' ';
                        }else{
                            unset($pathPieces[$key]);
                        }
                    }
                    $pathPieces[$key] = rtrim($pathPieces[$key]);
                }else{
                    if ($piece != 'index'){
                        $pathPieces[$key] = ucfirst($piece);
                    }else{
                        unset($pathPieces[$key]);
                    }
                }
            }
            $urlTitle = rtrim(join(' - ',$pathPieces), ' -').' - ';

            return $params['dynamic'] .' - '. $urlTitle . ucfirst($this->config->site->domain);
        }
    }

    function url($params=array())
    {
        return Wrapper::basePath().ltrim($params['_PATH'],'/');
    }

    function param($params=array())
    {
        return self::_getParam($_GET,self::_getParam($params,'name'));
    }

    function meta($params=array())
    {
        $metaString = '';
        foreach ($params as $key => $value){
            if (in_array($key,array('BLOCK','_PATH'))) continue;
            $metaString .= ' '.$key.'="'.$value.'"';
        }
        return '<meta'.$metaString.' />';
    }

    static function message($content='',$type='b') {
        if(!empty($content)) {
            $session = Wrapper::session('Page');
            $messages = $session->messages;

            is_array($messages) ?
                array_push( $messages, array('type'=>$type, 'content'=>$content) ) :
                $messages = array( array('type'=>$type, 'content'=>$content) );

            $session->messages = $messages;
        }

    }
}
