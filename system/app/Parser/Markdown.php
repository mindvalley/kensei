<?php
class Parser_Markdown extends Parser
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
        include(VENDOR_DIR.'Markdown.php');
        return Markdown($stringPieces);  //should be overloaded!
    }
}
