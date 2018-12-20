<?php
/**
* 2018 1024Mbits.com
*
* NOTICE OF LICENSE
*
*  @author    1024Mbits.com <soporte@1024mbits.com>
*  @copyright 2013-2018 1024Mbits.com
*  @license   Commercial license
*  @category  Prestashop
*  @category  Module
*
* You can not resell or redistribute this software.
*/
if (!defined('_PS_VERSION_')) 
    exit;

class PriceUpdateMail
{
    public static $email_subjects = array(
        'es' => 'Nuevo mensaje del módulo Actualización de Precios'
    );
    public static $messages         = array();
    private static $is_initialized  = false;
    private static $_debug          = false;
    private static $_active         = false;
    private static $mailto          = null;
    private static $shop            = null;
    private static $_id_lang        = null;
    private static $_language       = null;

    public static function message($message)
    {
        if (!self::$is_initialized) {
            self::init();
        }

        if (!empty($message)) {
            self::$messages[] = $message;
        }
    }

    public static function init($debug = false)
    {
        $id_employee = 1;//$this->context->employee->id;
        $employee = new Employee($id_employee ? $id_employee : 1);
        
        $mailto_default = Configuration::get('PS_SHOP_EMAIL');
        
        $sql = 'SELECT ftp_mail FROM `'._DB_PREFIX_.'pedregosa_config` ORDER BY `id_conn` DESC';
        $mailto_form = Db::getInstance()->getRow($sql);

        if ($debug) {
            self::$_debug = true;
        } else {
            self::$_debug = false;
        }

        if (!self::$mailto) {
            if(!$mailto_form['ftp_mail']) {
                self::$mailto = $mailto_default;
            }else {
                self::$mailto = $mailto_form['ftp_mail'];
            }
        }   

        if (!self::$shop) {
            self::$shop = Configuration::get('PS_SHOP_NAME');
        }

        if (!self::$_id_lang) {
            self::$_id_lang = $employee->id_lang;
            self::$_language = Language::getIsoById(self::$_id_lang);
        }
        self::$is_initialized = true;
    }

    public static function send()
    {
        if (!count(self::$messages)) {
            return (false);
        }

        if (!self::$is_initialized) {
            return (false);
        }

        if (isset(self::$email_subjects[self::$_language])) {
            $subject = self::$email_subjects[self::$_language];
        } else {
            $subject = self::$email_subjects['es'];
        }

        $template = 'plantilla'; // archivo template
        $template_vars = array();
        $template_vars['{message}'] = null;

        foreach (self::$messages as $message) {
            $template_vars['{message}'] .= nl2br($message);
        }
        try {
            Mail::Send(
                self::$_id_lang,
                $template, // template
                $subject, // subject
                $template_vars, // templateVars
                self::$mailto, // to
                null, // To Name
                self::$shop, // From
                null, // From Name
                null, // Attachment
                null, // SMTP
                _PS_MODULE_DIR_.'/priceupdate/mails/'
            );
        } catch (Exception $e) {
            return false;
        };

        return false;
    }
}