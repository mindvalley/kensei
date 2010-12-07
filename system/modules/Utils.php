<?php
/***
This file is part of Kensei.

Kensei is free software: you can redistribute it and/or modify
it under the terms of the Affero GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

Kensei is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
Affero GNU General Public License for more details.

You should have received a copy of the Affero GNU General Public License
along with Kensei.  If not, see <http://www.gnu.org/licenses/>.
***/


class Utils extends Lib{
    /** Standard Lib Functions START **/
    function __construct()
    {
        parent::__construct();
    }

    function scopeOf($functionName)
    {
        switch ($functionName){
            case 'javascript':
                return 'javascript';
                break;
            default:
                return '';
        }
    }
    /** Standard Lib Functions STOP **/

    /** Form Handlers START **/

    /** Form Handlers STOP **/

    /** Tags Methods START **/
    function basicAuth($params)
    {
        $login = self::_getParam($_SERVER,'PHP_AUTH_USER');
        $password = self::_getParam($_SERVER,'PHP_AUTH_PW');
        if (!empty($login) && !empty($password)){
            if ($login == $params['login'] && $password == $params['password']){
                return '';
            }
        }
        header('WWW-Authenticate: Basic realm="'.self::_getParam($params,'message','Please Login').'"');
        header('HTTP/1.0 401 Unauthorized');
        echo 'Invalid Username or Password.';
        exit();
    }

    function blank($params)
    {
        return '';
    }

    function getConfig($params)
    {
        $configVars = explode('.',$params['var']);
        $configData = $this->config;
        foreach ($configVars as $var){
            $configData = $configData->{$var};
        }
        return (string) $configData;
    }

    /**
     * returns email from cookie, get, post and session
     */
    function getEmail($params=array())
    {
        $session = Wrapper::session();
        if(!empty($_GET['email'])) {
            if (strstr($_GET['email'],'@') === false){
                $_GET['email'] = urldecode($_GET['email']);
            }
            $email = $_GET['email'];
        }elseif (!empty($_POST['email'])){
            $email = $_POST['email'];
        }elseif (!empty($_COOKIE['email'])){
            $email = $_COOKIE['email'];
        }elseif (!empty($session->email)){
            $email = $session->email;
        }
        $validator = new Zend_Validate_EmailAddress();
        if (!$validator->isValid($email)) {
            return '';
        }
        if(!empty($email)) {
            Wrapper::setCookie('email', $email,518400);
        }
        if (empty($_COOKIE['oemail'])){
            $domainLevels = explode('.',$_SERVER['SERVER_NAME']);
            $domainLevelCount = count($domainLevels);
            if ($domainLevelCount > 2){
                $domain = '.'.$domainLevels[$domainLevelCount - 2].'.'.$domainLevels[$domainLevelCount-1];
            }else{
                $domain = '.'.$_SERVER['SERVER_NAME'];
            }
            Wrapper::setCookie('oemail', $email,946707779,'/',$domain);
        }
        return $email;
    }

    /**
     * returns firstname from cookie, get, post and session
     */
    function getFirstName($params=array())
    {
        $session = Wrapper::session();
        if(!empty($_REQUEST['firstname'])) {
            $name = $_REQUEST['firstname'];
            $name = ucwords(strtolower($name));
        }elseif (!empty($_COOKIE['firstname'])){
            $name = $_COOKIE['firstname'];
        }elseif (!empty($session->firstname)){
            $name = $session->firstname;
        }else{
            $name='';
        }

        if(!empty($name)) {
            Wrapper::setCookie('firstname', $name,518400);
        }else{
            if (!self::_getParam($params,'nodefault')){
                $name = self::_getParam($params,'default', 'friend');
            }
        }

        return ucwords($name);
    }

    function htmlencode($params)
    {
        if ($params['no_compact'] == 1){
            return htmlentities($params['BLOCK']);
        }else{
            return str_replace(array("\n","\r","\l"),'',htmlentities($params['BLOCK']));
        }
    }


    function feed($params)
    {
        if (!empty($params['url_template'])){
            $params['url'] = $params['url_template'];
            $params['url'] = str_replace('<KEYWORD>','%22'.trim(basename(Wrapper::getUri(true)),'/').'%22',$params['url']);
        }

        if (!empty($params['url'])){
            try{
                $identifier = md5('rssFeed'.$params['url']);
                $feed = Wrapper::getCache($identifier);
                if (is_null($feed)){
                    $feed = Zend_Feed::import($params['url']);
                    Wrapper::setCache($identifier,$feed);
                }
                if (count($feed) < 1){
                    return '';
                }
                return $this->_getHtmlTemplate(self::_getParam($params,'template_file', 'feed.phtml'),array('feed'=>$feed,'params'=>$params));
            }catch(exception $e){
                return '';
            }
        }else{
            return "No Feed Url Provided";
        }
    }

    function redirectIfGetParam($params)
    {
        if ($params['value'] == $_GET[$params['name']]){
            $page = new Page();
            $page->redirect(array('with_query_string'=>'1','url'=>$params['url']));
        }
    }

    function includeUrl($params)
    {
        return file_get_contents($params['url']);
    }

    /**
     * Displays content of a block if a given cookie exists
     */
    function ifCookie($params)
    {
        $checkValue = self::_getParam($params,'value',$_COOKIE[$params['name']]);

        if(!empty($_COOKIE[$params['name']]) && $_COOKIE[$params['name']] == $checkValue) {
            return $params['BLOCK'];
        }
        return;
    }

    /**
     * Displays content of a block if a given cookie does not exist
     */
    function ifNotCookie($params)
    {
        if (empty($_COOKIE[$params['name']])){
            return $params['BLOCK'];
        }
        return;
    }

    /**
     * Displays content of a block if a parameter exists in URL
     */
    function ifGetParam($params)
    {
        if (!empty($_GET[$params['name']])){
            return $params['BLOCK'];
        }

        return ;
    }

     /*
      * Displays  parameter;
     */
    function urlPart($params)
    {
        $urlPart = self::_getParam($params,'url_part',1);
	      $filterUrlBasePath = self::_getParam($params,'filter_url_base_path',true);
        $keywords = explode('/',trim(Wrapper::getUri(false,$filterUrlBasePath),'/'));
        if (!empty($keywords[$urlPart])){
            if($params['remove_dash'] > 0){
                $words = explode('-',$keywords[$urlPart]);
                $ret = '';
                foreach ($words as $word){
                    $ret .= ucfirst($word).' ';
                }
                return trim($ret);
            }
            return $keywords[$urlPart];
        }

        return;
    }


    /**
     * Displays content of a block if a parameter does not exist in URL
     */
    function ifNotUrlPart($params)
    {
        $urlPart = self::_getParam($params,'url_part',1);
	      $filterUrlBasePath = self::_getParam($params,'filter_url_base_path',true);
        $keywords = explode('/',trim(Wrapper::getUri(false,$filterUrlBasePath),'/'));
        if (empty($keywords[$urlPart])){
            return $params['BLOCK'];
        }

        return;
    }

    /**
     * Displays content of a block if a parameter exists in URL
     */
    function ifUrlPart($params)
    {
        $urlPart = self::_getParam($params,'url_part',1);
	      $filterUrlBasePath = self::_getParam($params,'filter_url_base_path',true);
        $keywords = explode('/',trim(Wrapper::getUri(false),'/'));
        $keywords = explode('/',trim(Wrapper::getUri(),'/'));
        if (!empty($keywords[$urlPart])){
            return $params['BLOCK'];
        }

        return ;
    }

    function javascript($params)
    {
        if (empty($params['BLOCK'])){
            return "This tag must be a block please use utils_javascript_block";
        }
        return $params['BLOCK'];
    }

    function setTimeZone($params){
        $timeZone = self::_getParam($params,'timezone','UTC');
        date_default_timezone_set($timeZone);
    }

}
