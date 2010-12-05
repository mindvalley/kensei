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


class Wrapper
{
    static $theSession = null;

    static function httpRequest($url,$params,$type = 'POST',$headers=array(),$username='',$password='',$timeout=50,$allowRedirect = true){
        $type = strtoupper($type);
        $httpRequest = new Zend_Http_Client($url,array('timeout'=>$timeout));
        if (!$allowRedirect){
            $httpRequest->setConfig(array('maxredirects' => 0));
        }
        if ($type == 'POST'){
            $httpRequest->setParameterPost($params);
        }else{
            $httpRequest->setParameterGet($params);
        }
        if (!empty($username) && !empty($password)){
            $httpRequest->setAuth($username, $password);
        }
        if (count($headers) > 0){
            foreach ($headers as $headerName=>$headerData){
                $httpRequest->setHeaders($headerName,$headerData);
            }
        }
        return $httpRequest->request($type);
    }


    // Wrapper::setCookie()
    static function setCookie($name,$value,$expiry='10800',$path='/',$domain='',$secure='')
    {
        if (empty($domain)){
            $domain = $_SERVER['SERVER_NAME'];
        }
        if (empty($secure) && !empty($_SERVER['HTTPS'])){
            $secure=true;
        }else{
            $secure=null;
        }
        setcookie($name,$value,$expiry + time(),$path,$domain,$secure);
        $_COOKIE[$name] = $value;
    }
    /* $theSession = Wrapper::session('ecommerce');
     * echo $theSession->businessId
     * $theSession->businessId = 'moo';
    */
    static function session($namespace='indigo')
    {
        $config = Zend_Registry::get('config');
        if (!$config->cookinator->enabled){
            if (!isset(self::$theSession[$namespace])){
                self::$theSession[$namespace] = new Zend_Session_Namespace($namespace);
            }
            return self::$theSession[$namespace];
        }else{
            $cookinator = new Cookinator($namespace);
            $cookinator->setEncryptionKey($config->cookinator->encryptionKey);
            return $cookinator;
        }
    }

    static function sendEmail($from,$fromName,$name,$email,$subject, $body,$replyTo='')
    {
        $mailer = new Zend_Mail('UTF-8');
        $mailer->setBodyText($body);
        $mailer->setFrom($from, $fromName);
        if (!empty($replyTo)){
            $mailer->addHeader('Reply-To', $replyTo);
        }
        $mailer->addTo($email, $name);
        $mailer->setSubject($subject);
        return $mailer->send();
    }

    static function basePath(){
        $config = Zend_Registry::get('config');
        return $config->site->base_path;
    }

    static function baseUri($caseSensitive = false)
    {
        $uri = self::basePath();
        if ($caseSensitive){
            return (!empty($_SERVER['HTTPS'])) ? "https://".$_SERVER['SERVER_NAME'].$uri : "http://".$_SERVER['SERVER_NAME'].$uri;
        }else{
            return strtolower((!empty($_SERVER['HTTPS'])) ? "https://".$_SERVER['SERVER_NAME'].$uri : "http://".$_SERVER['SERVER_NAME'].$uri);
        }

    }

    static function mediaPath($subPaths=''){
        $config = Zend_Registry::get('config');
        $path = $config->site->base_path.$config->site->media_path;
        if (!empty($subPaths)){
            $path = rtrim($path,'/') . '/' . ltrim($subPaths,'/');
        }
        return $path;
    }

    static function jsPath($subPaths=''){
        $config = Zend_Registry::get('config');
        $path = $config->site->base_path.$config->site->js_path;
        if (!empty($subPaths)){
            $path = rtrim($path,'/') . '/' . ltrim($subPaths,'/');
        }
        return $path;
    }

    static function themesPath($subPaths=''){
        $config = Zend_Registry::get('config');
        $path = $config->site->base_path.$config->site->themes_path;
        if (!empty($subPaths)){
            $path = rtrim($path,'/') . '/' . ltrim($subPaths,'/');
        }
        return $path;
    }

    static function imagesPath($subPaths=''){
        $config = Zend_Registry::get('config');
        $path = $config->site->base_path.$config->site->images_path;
        if (!empty($subPaths)){
            $path = rtrim($path,'/') . '/' . ltrim($subPaths,'/');
        }
        return $path;
    }

    static function getUri($caseSensitive = false,$filterBasePath = true)
    {
        if ($caseSensitive){
            $path = $_SERVER['REQUEST_URI'];
            if ($filterBasePath){
                $path = str_replace(self::basePath(),'/',$path);
            }
        }else{
            $path = strtolower($_SERVER['REQUEST_URI']);
            if ($filterBasePath){
                $path = str_replace(strtolower(self::basePath()),'/',$path);
            }
        }
        return $path;
    }

    static function getFullUri($caseSensitive = false)
    {
        $uri = self::getUri($caseSensitive,false);
        if ($caseSensitive){
            return (!empty($_SERVER['HTTPS'])) ? "https://".$_SERVER['SERVER_NAME'].$uri : "http://".$_SERVER['SERVER_NAME'].$uri;
        }else{
            return strtolower((!empty($_SERVER['HTTPS'])) ? "https://".$_SERVER['SERVER_NAME'].$uri : "http://".$_SERVER['SERVER_NAME'].$uri);
        }
    }

    static function getFullCaptureUri($caseSensitive = false)
    {
        $config = Zend_Registry::get('config');
        if ($caseSensitive){
            return (!empty($_SERVER['HTTPS'])) ? "https://".$_SERVER['SERVER_NAME'].self::basePath().$config->form->action->capture : "http://".$_SERVER['SERVER_NAME'].$uri;
        }else{
            return strtolower((!empty($_SERVER['HTTPS'])) ? "https://".$_SERVER['SERVER_NAME'].self::basePath().$config->form->action->capture : "http://".$_SERVER['SERVER_NAME'].$uri);
        }
    }

    static function getRemoteIp()
    {
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])){
            // If there are commas, get the last one.. probably.
            if (strpos($_SERVER['HTTP_X_FORWARDED_FOR'], ',') !== false) {
                $ips = array_reverse(explode(', ', $_SERVER['HTTP_X_FORWARDED_FOR']));
                // Go through each IP...
                foreach ($ips as $i => $ip) {
                    // Make sure it's in a valid range...
                    if (preg_match('~^((0|10|172\.16|192\.168|255|127\.0)\.|unknown)~',$ip) != 0) continue;

                    // Otherwise, we've got an IP!
                    return trim($ip);
                    break;
                }
            }elseif (preg_match('~^((0|10|172\.16|192\.168|255|127\.0)\.|unknown)~', $_SERVER['HTTP_X_FORWARDED_FOR']) == 0){
                return $_SERVER['HTTP_X_FORWARDED_FOR'];
            }else{
                return $_SERVER['REMOTE_ADDR'];
            }
        }else{
            return $_SERVER['REMOTE_ADDR'];
        }
    }

    static function getCache($identifier,$timeout=3600,$cacheLocation = '/tmp/')
    {
        $cache = Zend_Cache::factory('Core',
                'FILE',
                array('lifetime' =>  $timeout
                        , 'automatic_serialization' => true,
                        'cache_id_prefix' => str_replace('.','',$_SERVER['SERVER_NAME'])),
                array('cache_dir' => $cacheLocation));

        if (!($cache->test($identifier))){
            return null;
        }else{
            return $cache->load($identifier);
        }
    }

    static function setCache($identifier,$data,$timeout=3600,$cacheLocation = '/tmp/')
    {
        $cache = Zend_Cache::factory('Core',
                'FILE',
                array('lifetime' =>  $timeout
                        , 'automatic_serialization' => true,
                        'cache_id_prefix' => str_replace('.','',$_SERVER['SERVER_NAME'])),
                array('cache_dir' => $cacheLocation,
                        'cache_file_umask' => 0666,
                        'hashed_directory_umask' => 0777));
        if (is_object($data)){
            $data = clone $data;
        }
        $cache->save($data,$identifier);
    }

    static function getLocationData()
    {
        $locationData = (array) GeoIpService::getLocationData(self::getRemoteIp());
        $stateList = Mindvalley_Location::getInternationalStateList();
        $regionCodeList = Mindvalley_Location::getRegionCodes();
        $stateId = array_search($locationData['state'],$stateList,false);
        $locationData['mindvalley_region'] = $regionCodeList[$stateId];
        if (!is_numeric($locationData['region'])){
            // Oap only supports 2 character TEXT
            $locationData['state_code'] = $locationData['region'];
        }
        unset($locationData['region']);
        return $locationData;
    }
}

