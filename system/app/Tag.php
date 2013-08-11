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


/**
*{{ <CLASS>_<FUNCTION>(_<TAGTYPE>) }}
*
*    * <CLASS> is a single worded name of a Php class in prodigy/lib directory.
*    * <FUNCTION> is the name of the function in the php class it can be saperated by underscrolls (_) however, the function name in the class should be in camel case ( thisIsCamelCase )
*    * <TAGTYPE> is optional, it denotes what type of tag is this. if this is not specified it will be defaulted to _FUNCTIONS There are 4 kinds of Tags:
*         1. _OPTION : Option tags are used to specify page options such as which layout to use, what page title to use, etc.
*         2. _FUNCTION : Function tags are the most commonly used tag
*         3. _BLOCK : Block Tags are simillar to html tags, content between the start tag and the close tag (prefixed with a front slash [/] ) are sent to the function for processing.
*         4. _CAPTURE : this tag returns nothing but it processes for http request. Although other tags could do the same, for performance reason this tag will be treated special and will be one of the first few functions that will be executed first.
*/
define('TAG_OPTION','option');
define('TAG_FUNCTION','function');
define('TAG_BLOCK','block');
define('TAG_RAWBLOCK','raw');
define('TAG_CAPTURE','capture');

class Tag
{
    static $data;
    static $translationData;
    static function init()
    {
        $config = Zend_Registry::get('config');
        self::$data = $config->tag;
    }

    static function _translate($text){
        $config = Zend_Registry::get('config');
        if (!$config->translation->enabled){
            return $text;
        }
        if (is_null(self::$translationData) && file_exists(BASE_DIR . 'translation.csv')){
            $handle = fopen(BASE_DIR . 'translation.csv', "r");
            self::$translationData = array();
            while (($data = fgetcsv($handle, 1000)) !== FALSE) {
                $data[0] = str_replace('\\"','"',$data[0]);
                $data[1] = str_replace('\\"','"',$data[1]);
                array_push(self::$translationData,array('original' =>trim($data[0]) , 'translated' => trim($data[1])));
            }
            fclose($handle);
        }

        if (!empty($text) && is_array(self::$translationData) && count(self::$translationData) > 0){
            foreach (self::$translationData as $translation){
                $text = str_replace($translation['original'],htmlentities($translation['translated'],ENT_COMPAT,'UTF-8'),$text);
            }
        }
        return $text;
    }
    /**
     * Returns the type of tag
     * @var string
     */
    static function getType($tag)
    {
        if (!isset(self::$data->$tag->type)){
            $tag = strtolower($tag);
            $tagLength = strlen($tag);
            if (substr_compare($tag,'option',$tagLength-6) == 0){
                return TAG_OPTION;
            }elseif (substr_compare($tag,'block',$tagLength-5) == 0){
                return TAG_BLOCK;
            }elseif (substr_compare($tag,'capture',$tagLength-7) == 0){
                return TAG_CAPTURE;
            }elseif (substr_compare($tag,'raw',$tagLength-3) == 0){
                return TAG_RAWBLOCK;
            }else{
                return TAG_FUNCTION;
            }
        }
        return self::$data->$tag->type;
    }

    /**
     * Returns the scope of tag
     * @var string
     */
    static function getScope($tag)
    {
        if (!isset(self::$data->$tag->scope)){
            $tagLength = strlen($tag);
            if (substr_compare($tag,'option',$tagLength-6) == 0){
                $tag = substr($tag,0,$tagLength-6);
            }elseif (substr_compare($tag,'block',$tagLength-5) == 0){
                $tag = substr($tag,0,$tagLength-5);
            }elseif (substr_compare($tag,'capture',$tagLength-7) == 0){
                $tag = substr($tag,0,$tagLength-7);
            }elseif (substr_compare($tag,'raw',$tagLength-3) == 0){
                $tag = substr($tag,0,$tagLength-3);
            }else{
                $tag = $tag;
            }
            $tagData = self::searchTagFunction($tag);
            if ($tagData == false){
                return '';
            }else{
                $tagLib = $tagData['class'];
                $tagFunctionName = $tagData['function'];
                $tagObject = new $tagLib;
                $scope = $tagObject->scopeOf($tagFunctionName);
                return $scope;
            }
        }
        return self::$data->$tag->scope;
    }

    /**
     * Returns the value of tag
     * @var string
     * @var array
     */
    static function getValue($tag,$parameter=array())
    {
        if (!isset(self::$data->$tag->value)){
            return self::execute($tag,$parameter);
        }
        return self::$data->$tag->value;
    }

    /**
     * if the tag must be dnyamic then returns true
     * @var string
     * @var array
     */
    static function isDynamic($tag)
    {
        if (!isset(self::$data->$tag->dynamic)){
            return false;
        }
        return self::$data->$tag->dynamic;
    }

    /**
     * if the tag must be dnyamic then returns true
     * @var string
     * @var array
     */
    static function isTranslatable($tag)
    {
        if (!isset(self::$data->$tag->translatable)){
            return true;
        }
        return self::$data->$tag->translatable;
    }

    /**
     * Returns the close tag of the provided tag
     * @var string
     */
    static function getCloseTag($tag)
    {
        return '/'.$tag;
    }

    /**
     * Returns true if its a close tag
     * @var string
     */
    static function isCloseTag($tag)
    {
        if (substr($tag,0,1) == '/'){
            return true;
        }else{
            return false;
        }
    }

    /**
     * returns an array maping of the tag's class and method.
     * @var string
     */
    static function searchTagFunction($tag)
    {

        if (!isset(self::$data->$tag->call)){
            list($tagLib,$tagFunctionName) = split('_',strtolower($tag),2);
            $functionName = split ('_',$tagFunctionName);
            $name='';
            foreach ($functionName as $key => $value){
                $name .= ucfirst($value);
            }

            $functionName = strtolower(substr($name,0,1)).substr($name,1);
            $tagFunctionName = $functionName;
        }else{
            list($tagLib,$tagFunctionName) = split('::',self::$data->$tag->call);
        }
        try{
            $tagLib = ucfirst($tagLib);
            if (@class_exists($tagLib) == true){
                $tagData['class'] = $tagLib;
                $tagData['function'] = $tagFunctionName;
                return $tagData;
            }else{
                return false;
            }
        }catch (exception $e){
            return false;
        }
    }

    /**
     * executes the tag with its parameter
     * @var string
     * @var string
     */
    static function execute($tag,$parameter)
    {
        $tagLength = strlen($tag);
        if (substr_compare($tag,'option',$tagLength-6) == 0){
            $tag = substr($tag,0,$tagLength-6);
        }elseif (substr_compare($tag,'block',$tagLength-5) == 0){
            $tag = substr($tag,0,$tagLength-5);
        }elseif (substr_compare($tag,'capture',$tagLength-7) == 0){
            $tag = substr($tag,0,$tagLength-7);
        }elseif (substr_compare($tag,'raw',$tagLength-3) == 0){
            $tag = substr($tag,0,$tagLength-3);
        }else{
            $tag = $tag;
        }
        $tagData = self::searchTagFunction($tag);

        if ($tagData === false){
            return '<div class="rp_warning"> <h1>ERROR TAG {{ "'.$tag.'" }} DOES NOT EXIST!</h1></div>';
        }else{
            $tagLib = $tagData['class'] ;
            $tagFunctionName = $tagData['function'];
            $tagObject = new $tagLib;
            $data = $tagObject->$tagFunctionName($parameter);
            $translatable = self::isTranslatable($tag);
            if ($tagObject->notTranslatable[$tagFunctionName] === true){
                $translatable = false;
            }
            if (!empty($data) && $translatable){
                 $data = self::_translate($data);
             }
            return $data;
        }

    }
}
