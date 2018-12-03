<?php
/**
* 2018 1024Mbits.com
*
* NOTICE OF LICENSE
*
*  @author    1024Mbits.com <soporte@1024mbits.com>
*  @copyright 2018 1024Mbits.com
*  @license   GNU General Public License version 2
*  @category  Prestashop
*  @category  Module
*
* You can not resell or redistribute this software.
*/

class priceupdateCronModuleFrontController extends ModuleFrontController
{
    public $module;

    public function __construct()
    {
        header('Access-Control-Allow-Origin: *');

        parent::__construct();
        
        $this->context = Context::getContext();

        Tools::setCookieLanguage($this->context->cookie);

        $protocol_link = (Configuration::get('PS_SSL_ENABLED') || Tools::usingSecureMode()) ? 'https://' : 'http://';
        if ((isset($this->ssl) && $this->ssl && Configuration::get('PS_SSL_ENABLED')) || Tools::usingSecureMode()) {
            $use_ssl = true;
        } else {
            $use_ssl = false;
        }

        $this->ssl = $use_ssl;

        $this->module = Module::getInstanceByName(Tools::getValue('module'));
        
        if($this->module && $this->module->active)
        {
            $log = $this->module->init();
        }else
        {
            header('Content-Type: text/plain');
            echo "modulo no encontrado";
        }
        header('Content-Type: text/plain');
        echo $log;
        die();
    }
}
