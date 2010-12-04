<?php
class Parser_Html extends Parser
{
    function __construct($fileLocation,$path)
    {
        parent::__construct($fileLocation,$path);
    }
    /**
    * Parse the text with Textile.
    * @var string
    */
    function parseText($stringPieces)
    {
        return $stringPieces; 
    }
}
