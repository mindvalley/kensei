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


class Config extends Zend_Config_Ini{
    /**
     *
     * Basically this class wraps the zend_config_ini class and loads all ini files in the config directory then finally loads manifest.ini.
     */
    public function __construct()
    {
        parent::__construct(CONFIG_DIR . 'application.ini',null,array('allowModifications'=>true));
        $directory = dir( CONFIG_DIR );
        while (($currentFile = $directory->read()) !== false){
            if ( $currentFile!='application.ini'
                    && substr($currentFile,strlen($currentFile)-4) == '.ini'){
                $this->merge(new Zend_Config_Ini(CONFIG_DIR . $currentFile,null,true));
            }
        }

        if (!file_exists(BASE_DIR . 'site.ini')){
            if (strstr($_SERVER['SERVER_NAME'],'.dev') !== false){
                die('site.ini doesn\'t exist, please copy it from the indigo repo');
            }
        }else{
            $this->merge(new Zend_Config_Ini(BASE_DIR . 'site.ini'));
        }
        $this->merge(new Zend_Config_Ini(BASE_DIR . 'manifest.ini'));
        if (!empty($this->ecommerce->buisnessId)){
            $this->ecommerce->businessId = $this->ecommerce->buisnessId;
            if (strstr($_SERVER['SERVER_NAME'],'.dev') !== false){
                die('Business is spelled wrongly in manifest.ini please correct it.');
            }
        }
    }
}
