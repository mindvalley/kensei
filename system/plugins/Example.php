<?php
class Example extends Lib{
    /** Standard Lib Functions START **/
    function __construct()
    {
        parent::__construct();
    }

    function scopeOf($functionName)
    {
        switch ($functionName){
            default:
                return '';
        }
    }
    /** Standard Lib Functions STOP **/

    /** Form Handlers START **/

    function requestTag()
    {

        Page::message('Indigo: In The Go!!! Your Ip address is:'.Wrapper::getRemoteIp());
        return Wrapper::basePath();
    }

    /** Form Handlers STOP **/

    /** Tags Methods START **/

    function tag($params)
    {
        return self::_getParam($params,'output','hello world');
    }
}
