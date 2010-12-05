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
class Parser {
    var $fileLocation;
    var $pageScope;
    var $config;
    var $outputCode;
    var $path;
    var $translationData;
    var $overidingScopes;
    /**
    * constructs the parser
    * @var string
    * @var string
    */
    function __construct($fileLocation,$path)
    {
        $this->fileLocation = $fileLocation;
        $this->path = $path;
        $this->translationData = NULL;
        if (!Zend_Registry::isRegistered('config')){
            $this->config  = new Config();
            Zend_Registry::set('config', $this->config );
        }else{
            $this->config = Zend_Registry::get('config');
        }
        if (isset($this->config->scope->override)){
            $this->overidingScopes = $this->config->scope->override->toArray();
        }
    }

    /**
    * Converts the tags into its respective outputs.
    * @var string
    * @var string
    * @var string
    */
    protected function _convertTag($tagClassName, $tagData,$block='',$raw='')
    {
        if (Tag::isCloseTag($tagClassName)){
            return '';
        }

        $param = $this->_parseNameValues($tagData);
        if (!empty($block)){
            $param['BLOCK'] = $block;
            $param['RAW'] = $raw;
        }

        $param['_PATH'] = $this->path;
        $scope = trim(Tag::getScope($tagClassName));
        // call the function then put into the result
        if (Tag::getType($tagClassName) == TAG_OPTION){
            $data = Tag::getValue($tagClassName,$param);
        }else{
            $data = Tag::execute($tagClassName,$param);
        }

        if (!empty($scope)){
            if (!in_array($scope,$this->overidingScopes)){
                $this->pageScope[$scope] .= $data;
            }else{
                $this->pageScope[$scope] = $data;
            }
            return '';
        }

        return $data;

    }

    /**
    * Get the array mapping for parameters and its value
    * @var string
    */
    protected function _parseNameValues ($text)
    {
        $values = array();
        $text = str_replace('\\"',"&quot;",$text);
        $text = str_replace("\\'","&#39;",$text);
        $text = str_replace('\\$','$',$text);
        $text = str_replace('\\{','{',$text);
        $text = str_replace('\\}','}',$text);
        if (preg_match_all('/([^=\s]+)(\s|)=(\s|)("(?P<value1>[^"]+)"|\'(?P<value2>[^\']+)\'|(?P<value3>.+?)\b)/', $text, $matches, PREG_SET_ORDER))
            foreach ($matches as $match)
                $values[trim($match[1])] = trim(@$match['value1'] . @$match['value2'] . @$match['value3']);
        return $values;
    }

    /**
    * Converts the content to the end result.
    * @var &string
    */
    public function convertContent(&$content,$noParse = false)
    {
        $contentStack = array();
        $tagStack = array();
        $stripped = '';
        $newContent = '';
        while (!empty($content)){
            $tagOpenPosition = strpos($content,'{{');
            $tagClosePosition = strpos($content,'}}',$tagOpenPosition);
            if ($tagOpenPosition !== false){
                $tempContent = substr($content,0,$tagOpenPosition);
                $tag = trim(substr($content,$tagOpenPosition,$tagClosePosition- $tagOpenPosition),'{} ');
                $content = substr($content,$tagClosePosition + 2);
                @list($tagClassName, $tagData) = preg_split('/[\s,]+/', $tag ,2);

                if (Tag::getType($tagClassName) == TAG_BLOCK){
                    //get closetag location
                    $closeTagPos = strpos($content, Tag::getCloseTag($tagClassName));
                    while (strlen($content) > $closeTagPos + 1 && !in_array(substr($content,$closeTagPos - 1,1),array(' ','{',"\n")) ){
                        $prevPos = $closeTagPos;
                        $closeTagPos = strpos($content, Tag::getCloseTag($tagClassName), $closeTagPos + 1);
                        if ($closeTagPos === false){
                            $closeTagPos = $prevPos;
                            break;
                        }
                    }
                    $nestedContent = substr($content,0,$closeTagPos);
                    $nestedContent = substr($content,0,strrpos($nestedContent,'{{'));
                    $content = substr($content,strlen($nestedContent));
                    $raw = $nestedContent;
                    $nestedData = $this->convertContent($nestedContent);
                    $tag = $this->_convertTag($tagClassName, $tagData,$nestedData,$raw);
                }elseif (Tag::getType($tagClassName) == TAG_RAWBLOCK){
                    //get closetag location
                    $closeTagPos = strpos($content, Tag::getCloseTag($tagClassName));
                    while (strlen($content) > $closeTagPos + 1 && !in_array(substr($content,$closeTagPos - 1,1),array(' ','{',"\n")) ){
                        $prevPos = $closeTagPos;
                        $closeTagPos = strpos($content, Tag::getCloseTag($tagClassName), $closeTagPos + 1);
                        if ($closeTagPos === false){
                            $closeTagPos = $prevPos;
                            break;
                        }
                    }
                    $rawContent = substr($content,0,$closeTagPos);
                    $rawContent = substr($content,0,strrpos($rawContent,'{{'));
                    $content = substr($content,strlen($rawContent));
                    $raw = $rawContent;
                    $rawContent = $this->convertContent($rawContent,true);
                    $tag = $this->_convertTag($tagClassName, $tagData,$rawContent,$raw);
                }else{
                    $tag = $this->_convertTag($tagClassName, $tagData);
                }

                if (Tag::getType($tagClassName) == TAG_CAPTURE){
                    $newContent = $tag;
                    $tag = '';
                }

                if (Tag::isCloseTag($tagClassName)){
                    $tag = '';
                }

                array_push($tagStack,$tag);
            }else{
                $tempContent = $content;
                $content = '';
            }

            array_push($contentStack,$tempContent);
        }
        if (!$noParse){
            $contentStack = join('{{#}}',$contentStack);
            $contentStack = explode('{{#}}',$this->parseText($contentStack));
        }
        $totalContent = count ($contentStack);
        for ($index=0;$index<$totalContent;$index++) {
            $contentLength = strlen($contentStack[$index]);
            $cleanedHtml = $contentStack[$index];

            $contentLength = strlen($contentStack[$index]);
            if ($contentLength >= 4 && substr_compare($contentStack[$index],'</p>',0,4,false) == 0){
                $cleanedHtml = substr($contentStack[$index],4);
            }

            $cleanLength = strlen($cleanedHtml);
            if ($cleanLength >= 3 && substr_compare($cleanedHtml,'<p>',$cleanLength-3,3,false) == 0){
                $cleanedHtml = substr($cleanedHtml,0,$cleanLength-3);
            }else{
                //$cleanedHtml .="\n";
            }

            $newContent .=  $cleanedHtml . $tagStack[$index];
        }
        $newContent = str_replace("<p></p>","",$newContent);
        return $newContent;
    }

    function injectScope($scopeArray=array(),$forceOverride=false){
        foreach ($scopeArray as $scope => $scopeValue){
            if (!$forceOverride && !in_array($scope,$this->overidingScopes)){
                $this->pageScope[$scope] .= $scopeValue;
            }else{
                $this->pageScope[$scope] = $scopeValue;
            }
        }
    }

    function getScope($scope){
        if (isset($this->pageScope[$scope])){
            return $this->pageScope[$scope];
        }
        return false;
    }

    /**
    * Parse the text with something, should be overloaded or will return what its passed
    * @var string
    */
    function parseText($stringPieces)
    {
        return $stringPieces;  //should be overloaded!
    }

    /**
    * generates the content
    * when $outputCode is set to true, it will generate php codes.
    * @var boolean
    */
    function render($outputCode = false)
    {
        $this->outputCode = $outputCode;
        if (!empty($this->fileLocation)){
            $content = file_get_contents($this->fileLocation);
        }
        $tempContent = trim($content);
        $tempContent = $this->convertContent($tempContent);

        if (!empty($this->pageScope['content'])){
            $this->pageScope['content'] .= $tempContent;
        }else{
            $this->pageScope['content'] = $tempContent;
        }
        $layout = USER_LAYOUTS_DIR . $this->pageScope['layout'];
        if (!file_exists($layout)){
            $layout =  LAYOUTS_DIR . $this->pageScope['layout'];
            if (!file_exists($layout)){
                $layout =  LAYOUTS_DIR . 'index.phtml';
            }
        }
        //$renderedContent = $this->pageScope['header'];
        $renderedContent = $this->renderPhpFile($layout,$this->pageScope);
        return $renderedContent;
    }

    /**
    * Renders php files and generate pure html.
    * @var string
    * @var array
    */
    public function renderPhpFile($fileName,$passVariables = array())
    {
        foreach ($passVariables as $key => $data){
                $$key = $data;
        }
        ob_start();
        include($fileName);
        $data = ob_get_contents();
        ob_end_clean();
        return $data;
    }

}
