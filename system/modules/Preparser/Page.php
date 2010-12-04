<?php
class Preparser_Page extends Preparser
{
    function __construct()
    {
        parent::__construct();
    }

    function init() {
        $pageScope = array();
        list($currentUri,$query) = explode('?',Wrapper::getUri());
        $path = trim($currentUri,'/');
        if (!empty($path)){
            $jsPath = $path;
            $dashLocation = strrpos($path,'-');
            if ($dashLocation!==false){
                if (is_numeric(substr($path,$dashLocation))){
                    $jsPath = substr($path,0,$dashLocation);
                }
            }
            $jsPath = Wrapper::jsPath() . '/partials/'.$jsPath.'.js';
        }else{
            $jsPath = Wrapper::jsPath() . '/partials/index.js';
        }
        if (file_exists(MEDIA_DIR.$jsPath)){
            $pageScope['javascript'] = '<script src="'.$jsPath.'" type="text/javascript"></script>'."\n";
        }
        $jsData = $this->config->layout->js;
        if (count($jsData) > 0){
            foreach ($jsData as $js){
                if (substr_compare($js,".js",strlen($js)-3,3,false) !=0) $js .= '.js';
                if (!$this->config->layout->jqueryNotInTemplate && $js == 'jquery.min.js') continue;
                $pageScope['javascript'] .= '<script src="'.Wrapper::jsPath().$js.'" type="text/javascript"></script>'."\n";
            }
        }

        //USER_LAYOUTS_DIR
        $pageScope['layout'] =  $this->config->layout->default . '.phtml';

        $pageHeader = USER_LAYOUTS_DIR . $pageScope['pageHeader'];
        if (!file_exists($pageHeader)){
            $pageHeader = LAYOUTS_DIR . $pageScope['pageHeader'];
        }
        $pageScope['pageHeader'] = $pageHeader;
        $pageFooter = USER_LAYOUTS_DIR . $pageScope['pageFooter'];
        if (!file_exists($pageFooter)){
            $pageFooter =  LAYOUTS_DIR . $pageScope['pageFooter'];
        }
        $pageScope['pageFooter'] = $pageFooter;

        $pageScope['content'] = '';

        if (isset($this->config->layout->css)){
			foreach ($this->config->layout->css as $css){
                $css = Wrapper::basePath().ltrim($css,'/');
				$pageScope['style'] .= '@import url("'.$css.'");'."\n";
			}
        }

        $path = trim($path,'/');
        if (empty($path)){
            $pageScope['bodyAttrib'] = 'id="index"';
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
                $pageScope['bodyAttrib'] = 'id="'.$id.'" class="'.$class.'"';
            }else{
                $pageScope['bodyAttrib'] = 'id="'.$path.'"';
            }

        }

        $pageScope['title'] = $this->config->site->title;
        $pageScope['name'] = $this->config->site->name;
        $pageScope['description'] = $this->config->meta->description;
        $pageScope['keywords'] = $this->config->meta->keywords;

        if (isset($_GET['email']) || isset($_GET['firstname'])){
            $utils = new Utils();
            $utils->getEmail();
            $utils->getFirstName();
        }

        return $pageScope;
    }
}
