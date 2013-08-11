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



abstract class Preparser
{
    var $config;

    function __construct()
    {
        $this->config = Zend_Registry::get('config');
    }
    abstract function init();
}
