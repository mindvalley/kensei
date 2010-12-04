<?php
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
