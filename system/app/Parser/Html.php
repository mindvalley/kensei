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
