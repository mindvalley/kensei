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
class Style extends Lib{
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
    /** Form Handlers STOP **/
    /** Tags Methods START **/

    function __call($name,$params)
    {
        $params = $params[0];
        $params['BLOCK'] = trim($params['BLOCK']);
        $params['BLOCK'] = str_replace(array('<p>','</p>'),'',$params['BLOCK']);
        $style = self::_getParam($params,'style',' style="text-align:center;"',' style="','"');
        $class = self::_getParam($params,'class','',' class="','"');
        $tagId = self::_getParam($params,'id','',' id="','"');
        switch ($name){
            case 'h1':
                if (!Zend_Registry::isRegistered('H1Registered')){
                    Zend_Registry::set('H1Registered',true);
                    self::_injectScope('description',strip_tags($params['BLOCK']));
                }
                return '<h1'.$tagId.$style.$class.'>'.$params['BLOCK'].'</h1>';
            case 'h2':
                return '<h2'.$tagId.$style.$class.'>'.$params['BLOCK'].'</h2>';
            case 'h3':
                return '<h3'.$tagId.$style.$class.'>'.$params['BLOCK'].'</h3>';
            case 'h4':
                return '<h4'.$tagId.$style.$class.'>'.$params['BLOCK'].'</h4>';
            case 'h5':
                return '<h5'.$tagId.$style.$class.'>'.$params['BLOCK'].'</h5>';
        }
        return parent::__call($name,$params);
    }
    /** Tags Methods STOP **/

    /** Internal function START **/

    private function _div($class,$content,$style = '')
    {
        return '
        <div class="'.$class.'" style="'.$style.'">
            '.$content.'
        </div>';
    }

}
