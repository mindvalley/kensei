<?php
/*
 *      Cookinator.php
 *
 *      Copyright 2009 Calvin <calvin@collectskin.com>
 *
 *      This program is free software; you can redistribute it and/or modify
 *      it under the terms of the GNU General Public License as published by
 *      the Free Software Foundation; either version 2 of the License, or
 *      (at your option) any later version.
 *
 *      This program is distributed in the hope that it will be useful,
 *      but WITHOUT ANY WARRANTY; without even the implied warranty of
 *      MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *      GNU General Public License for more details.
 *
 *      You should have received a copy of the GNU General Public License
 *      along with this program; if not, write to the Free Software
 *      Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 *      MA 02110-1301, USA.
 *
 *      Example Usage:
 *      $cookinator = new Cookinator();
 *      $cookinator->apple = 'holy cow';
 *      echo 'My Cookie is : '.$cookinator->apple;
 */
class Cookinator
{
    private $_expiry;
    private $_path;
    private $_domain;
    private $_secure;
    private $_namespace;
    private $_encryptionKey;
    function __construct($namespace='cokinator',$expiry='10800',$path='/',$domain=null,$secure='')
    {
        $this->setEncryptionKey('I will cookinate you!!');
        if (is_null($domain)){
            $this->_domain = $_SERVER['SERVER_NAME'];
        }else{
            $this->_domain = $domain;
        }
        $this->_expiry = $expiry;
        $this->_path = $path;
        if (empty($secure) && !empty($_SERVER['HTTPS'])){
            $this->_secure=true;
        }else{
            $this->_secure=null;
        }
        $this->_namespace = $namespace . '_';
    }

    public function debug(){
        $string = '';
        foreach ($_COOKIE as $key=>$value){
            if (strlen($this->_namespace) < strlen($key) && substr_compare($key,$this->_namespace,0,strlen($this->_namespace)) == 0){
                $value = $this->_decrypt($_COOKIE[$key]);
                if (!empty($value)){
                    $string .= $key.'='.var_export(unserialize($value),true).'<br />';
                }
            }
        }
        return $string;
    }

    public function __get($name)
    {
        if (isset($_COOKIE[$this->_namespace.$name]))
        {
            $value = $this->_decrypt($_COOKIE[$this->_namespace.$name]);
            if (!empty($value)){
                return unserialize($value);
            }
        }
        return null;
    }

    public function __isset($name)
    {
        return isset($_COOKIE[$this->_namespace.$name]);
    }

    public function __set($name, $value)
    {
        $value = serialize($value);
        $value = $this->_encrypt($value);
        $name = $this->_namespace.$name;
        $expiry = $this->_expiry + time();
        $path = $this->_path;
        $domain = $this->_domain;
        $secure = $this->_secure;
        $result = setcookie($name,$value,$expiry,$path,$domain,$secure);
        $_COOKIE[$name] = $value;
    }

    public function __unset($name)
    {
        setcookie($this->_namespace.$name,null,time() - 3600,$this->_path,$this->_domain,$this->_secure);
    }

    private function _encrypt($value)
    {
        $ivSize = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
        $iv = mcrypt_create_iv($ivSize, MCRYPT_RAND);
        $encrypted = mcrypt_encrypt(MCRYPT_RIJNDAEL_256,$this->_encryptionKey,$value, MCRYPT_MODE_ECB,$iv);
        return base64_encode($encrypted);
    }
    private function _decrypt($value)
    {
        $value = base64_decode($value);
        $ivSize = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
        $iv = mcrypt_create_iv($ivSize, MCRYPT_RAND);
        return rtrim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $this->_encryptionKey,$value, MCRYPT_MODE_ECB,$iv),"\0");
    }

    public function setEncryptionKey($key){
        $this->_encryptionKey = hash('sha256',$key,true);
    }
}
