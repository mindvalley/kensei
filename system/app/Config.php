<?php
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
