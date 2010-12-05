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


class Parser_Textile extends Parser
{
    private $_textileParser;
    function __construct($fileLocation,$path)
    {
        parent::__construct($fileLocation,$path);

        if (!Zend_Registry::isRegistered('textile')){
            $this->_textileParser = new Textile();
            Zend_Registry::set('textile', $this->_textileParser);
        }else{
            $this->_textileParser = Zend_Registry::get('textile');
        }
    }
    /**
    * Parse the text with Textile.
    * @var string
    */
    function parseText($stringPieces)
    {
        if ($this->config->textile->globalCaching){
            $identifier = $identifier = hash('md4',$stringPieces);
            $cacher = Wrapper::getCache($identifier,2592000, BASE_DIR.'/cache/');
            if (is_null($cacher)){
                $data = $this->_textileParser->TextileThis($stringPieces);
                Wrapper::setCache($identifier,$data,2592000, BASE_DIR.'/cache/');
                return $data;
            }else{
                return $cacher;
            }
        }else{
            return $this->_textileParser->TextileThis($stringPieces);
        }
    }
}
