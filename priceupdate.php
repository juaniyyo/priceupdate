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

require_once(dirname(__FILE__) . '/vendor/autoload.php');
require_once _PS_MODULE_DIR_.'/priceupdate/classes/PriceUpdateCommon.php';
require_once _PS_MODULE_DIR_.'/priceupdate/classes/PriceUpdateMail.php';


class PriceUpdate extends Module
{
	/**
	 * @var boolean
     * 
	 */
	public $error = false;
	public $csv;
	public $env;

	public function __construct()
	{
		$this->name = 'priceupdate';
		$this->tab = 'administration';
		$this->version = '1.0';
		$this->author = 'Juan M. Rube';
		$this->author_uri = 'https://www.1024mbits.com';
		$this->need_instance = 0;
		$this->bootstrap = true;

		/**
		 * Libreria que carga configuraciones en archivos .env y están disponibles en forma global
		 * @return instacia de Dotenv
		 */
		$this->csv = new PriceUpdateCommon();
		
		parent::__construct();

		$this->displayName = $this->l('Actualizador de Precios');
		$this->description = $this->l('Actualiza precios desde CSV descargado del proveedor.');
		$this->logo_path = $this->_path.'logo.png';
		$this->confirmUninstall = $this->l('¿Esta seguro que desea desinstalar el módulo actualizador de precios?');
		$this->ps_versions_compliancy = array('min' => '1.6', 'max' => '1.6.99.99');
	}

	public function install()
	{
		if (!parent::install())
			return false;
		
		Configuration::updateValue('JMR_PU_LASTUPDATE', 'null');
		Configuration::updateValue('JMR_PU_REMOTE_FILE', 'null');
		Configuration::updateValue('JMR_PU_OFFSET', 'null');
		Configuration::updateValue('JMR_PU_REFERENCE', 'null');
		Configuration::updateValue('JMR_PU_LPI', 'null');
		Configuration::updateValue('JMR_PU_PRICE', 'null');
		$success = include(dirname(__FILE__) . '/sql/install.php');
		
		if (!$success)
		{
			parent::uninstall();

			return false;
		}

		return true;
	}

	public function uninstall()
	{
		$success = include(dirname(__FILE__) . '/sql/uninstall.php');

		if (!parent::uninstall() 
		|| !$success
		|| !Configuration::deleteByName('JMR_PU_LASTUPDATE')
		|| !Configuration::deleteByName('JMR_PU_REMOTE_FILE')
		|| !Configuration::deleteByName('JMR_PU_OFFSET')
		|| !Configuration::deleteByName('JMR_PU_REFERENCE')
		|| !Configuration::deleteByName('JMR_PU_LPI')
		|| !Configuration::deleteByName('JMR_PU_PRICE')
		)
			//return false;

		return true;
	}

	/**
	 * @param $id
	 *
	 * @return bool|array
	 */
	public function getConnById($id)
	{
		if ((int)$id > 0)
		{
			$sql = 'SELECT b.`id_conn`, b.`ftp_name`, b.`ftp_srv`, b.`ftp_user`, b.`ftp_mail`, b.`ftp_pass`, b.`ftp_ssl` 
					FROM `'._DB_PREFIX_.'pedregosa_config` b WHERE b.id_conn='.(int)$id;

			if (!$results = Db::getInstance()->getRow($sql))
			{
				return false;
			}

			$link['id_conn'] = (int)$results['id_conn'];
			$link['ftp_name'] = $results['ftp_name'];
			$link['ftp_srv'] = $results['ftp_srv'];
			$link['ftp_user'] = $results['ftp_user'];
			$link['ftp_mail'] = $results['ftp_mail'];
			$link['ftp_pass'] = $results['ftp_pass'];
			$link['ftp_ssl'] = $results['ftp_ssl'];

			$link['csv_remote'] = (Configuration::get('JMR_PU_REMOTE_FILE') ? Configuration::get('JMR_PU_REMOTE_FILE') : 'N/D');
			$link['csv_offset'] = (Configuration::get('JMR_PU_OFFSET') ? Configuration::get('JMR_PU_OFFSET') : 'N/D');
			$link['csv_reference'] = (Configuration::get('JMR_PU_REFERENCE') ? Configuration::get('JMR_PU_REFERENCE') : 'N/D');
			$link['csv_price'] = (Configuration::get('JMR_PU_PRICE') ? Configuration::get('JMR_PU_PRICE') : 'N/D');

			return $link;
		}

		return false;
	}

	public function getConn()
	{
		$result = array();

		$sql = 'SELECT b.`id_conn`, b.`ftp_name`, b.`ftp_srv`, b.`ftp_user`, b.`ftp_mail`, b.`ftp_pass`, b.`ftp_ssl` 
				FROM `'._DB_PREFIX_.'pedregosa_config` b ORDER BY `id_conn` DESC';

		if (!$links = Db::getInstance()->executeS($sql))
		{
			return false;
		}

		$i = 0;
		foreach ($links as $link) 
		{
			$result[$i]['id_conn'] = $link['id_conn'];
			$result[$i]['ftp_name'] = $link['ftp_name'];
			$result[$i]['ftp_srv'] = $link['ftp_srv'];
			$result[$i]['ftp_user'] = $link['ftp_user'];
			$result[$i]['ftp_mail'] = $link['ftp_mail'];
			$result[$i]['ftp_pass'] = $link['ftp_pass'];
			$result[$i]['ftp_ssl'] = ($link['ftp_ssl'] == 1 ? "Activado" : "Desactivado");

			$result[$i]['csv_remote'] = (Configuration::get('JMR_PU_REMOTE_FILE') ? Configuration::get('JMR_PU_REMOTE_FILE') : 'N/D');
			$result[$i]['csv_offset'] = (Configuration::get('JMR_PU_OFFSET') ? Configuration::get('JMR_PU_OFFSET') : 'N/D');
			$result[$i]['csv_reference'] = (Configuration::get('JMR_PU_REFERENCE') ? Configuration::get('JMR_PU_REFERENCE') : 'N/D');
			$result[$i]['csv_lpi'] = (Configuration::get('JMR_PU_LPI') ? Configuration::get('JMR_PU_LPI') : 'N/D');
			$result[$i]['csv_price'] = (Configuration::get('JMR_PU_PRICE') ? Configuration::get('JMR_PU_PRICE') : 'N/D');

			$i++;
		}

		return $result;
	}

	public function addConn()
	{
		if (!Db::getInstance()->execute('INSERT INTO '._DB_PREFIX_.'pedregosa_config (`id_conn`, `ftp_name`, `ftp_srv`, `ftp_user`, `ftp_mail`, `ftp_pass`, `ftp_ssl`)
			VALUES (NULL, \''.pSQL(Tools::getValue('ftp_name')).'\', \''.pSQL(Tools::getValue('ftp_srv')).'\', \''.pSQL(Tools::getValue('ftp_user')).'\',\''.pSQL(Tools::getValue('ftp_mail')).'\', \''.pSQL(Tools::getValue('ftp_pass')).'\', '.((isset($_POST['ftp_ssl']) && Tools::getValue('ftp_ssl')) == 'on' ? 1 : 0).')') 
		|| !$id_link = Db::getInstance()->Insert_ID())
		{ 
			return false; 
		}

		return true;
	}

	public function addCsvParam()
	{
		if (!Configuration::updateValue('JMR_PU_REMOTE_FILE', pSQL(Tools::getValue('csv_remote'))) || !Configuration::updateValue('JMR_PU_OFFSET', pSQL(Tools::getValue('csv_offset')))
			|| !Configuration::updateValue('JMR_PU_REFERENCE', pSQL(Tools::getValue('csv_reference'))) || !Configuration::updateValue('JMR_PU_LPI', pSQL(Tools::getValue('csv_lpi')))
			|| !Configuration::updateValue('JMR_PU_PRICE', pSQL(Tools::getValue('csv_price'))) || !Configuration::updateValue('JMR_PU_STOCK', pSQL(Tools::getValue('csv_stock'))))
			{
				return false;
			}
			
			return true;
	}

	public function deleteConn()
	{		
		$this->deleteCsvParam();
		return Db::getInstance()->execute('TRUNCATE TABLE '._DB_PREFIX_.'pedregosa_config');
	}

	public function deleteCsvParam()
	{
		Configuration::deleteByName('JMR_PU_REMOTE_FILE');
		Configuration::deleteByName('JMR_PU_OFFSET');
		Configuration::deleteByName('JMR_PU_REFERENCE');
		Configuration::deleteByName('JMR_PU_LPI');
		Configuration::deleteByName('JMR_PU_PRICE');
	}

	public function getContent()
	{
		$this->_html = '';

		$cron_command = $this->csv->getCronCommand();
        $php_dir = $this->csv->getPHPExecutableFromPath();
        $url = $this->csv->getUrlCommand();
        $domain = Configuration::get('PS_SHOP_DOMAIN');

		$this->_html .= '<div class="panel panel-success">';
		$this->_html .= '<div class="panel-heading"> Configuración de tarea cron </div>';
		$this->_html .= '<div class="alert alert-info"><p>Copia y pega este comando en la <b>lista de tareas cron</b> del servidor</p>';
		$this->_html .= '<pre>' . $php_dir . ' ' . $cron_command . '</pre>';
		$this->_html .= '<p>En caso de error o para ejecutar manualmente la tarea de actualización ingresa en la siguiente URL</p>';
		$this->_html .= '<pre>' . $domain . '/index.php' . $url . '</pre>';
		$this->_html .= '</div>';

		if (Tools::isSubmit('submitFtp'))
		{
			if (empty($_POST['ftp_srv']) || empty($_POST['ftp_user']) || empty($_POST['ftp_pass']))
			{
				$this->_html .= $this->displayError($this->l('Tienes que completar todos los datos'));
			}else
			{
				if ($this->addConn())
				{
					Tools::redirectAdmin($this->context->link->getAdminLink('AdminModules').'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name);
					$this->_html .= $this->displayConfirmation($this->l('La conexión FTP ha sido agregada correctamente.'));
				}
				else
				{
					$this->_html .= $this->displayError($this->l('Ha ocurrido un error durante la creación del conexión'));
				}
			}
		}
		elseif (Tools::isSubmit('submitCsv'))
		{
			if (empty($_POST['csv_remote']) || empty($_POST['csv_offset']) || empty($_POST['csv_reference']) || empty($_POST['csv_lpi']) 
				|| empty($_POST['csv_price']))
			{
				$this->_html .= $this->displayError($this->l('Tienes que completar todos los datos'));
			}else
			{
				if ($this->addCsvParam())
				{
					Tools::redirectAdmin($this->context->link->getAdminLink('AdminModules').'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name);
					$this->_html .= $this->displayConfirmation($this->l('La configuración del CSV ha sido agregada correctamente.'));
				}
				else
				{
					$this->_html .= $this->displayError($this->l('Ha ocurrido un error durante la configuración del CSV'));
				}
			}
		}
		// borrar conexión ftp
		elseif (Tools::isSubmit('deletepriceupdate') && Tools::getValue('id_conn'))
		{

			if (!is_numeric(Tools::getValue('id_conn')) || !$this->deleteConn())
			{
				$this->_html .= $this->displayError($this->l('Ha ocurrido un error al borrar la conexión'));
			}
			else
			{
				Tools::redirectAdmin($this->context->link->getAdminLink('AdminModules').'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name);
				$this->_html .= $this->displayConfirmation($this->l('Conexión borrada correctamente.'));
			}
		}

		$this->_html .= $this->renderForm();
		$this->_html .= $this->renderList();

		return $this->_html;
	}

	public function renderList()
	{
		$fields_list = array(
			'ftp_name' => array(
				'title' => $this->l('Nombre de la conexión'),
				'type' => 'text',
			),
			'ftp_srv' => array(
				'title' => $this->l('Servidor FTP'),
				'type' => 'text',
			),
			'ftp_user' => array(
				'title' => $this->l('Usuario FTP'),
				'type' => 'text',
			),
			'ftp_mail' => array(
				'title' => $this->l('Notificaciones'),
				'type' => 'text',
			),
			'csv_remote' => array(
				'title' => $this->l('Archivo CSV Remoto'),
				'type' => 'text',
			),
			'csv_offset' => array(
				'title' => $this->l('CSV Offset'),
				'type' => 'text',
			),
			'csv_reference' => array(
				'title' => $this->l('CSV columna Referencia'),
				'type' => 'text',
			),
			'csv_lpi' => array(
				'title' => $this->l('CSV LPI'),
				'type' => 'text',
			),
			'csv_price' => array(
				'title' => $this->l('CSV columna Precio'),
				'type' => 'text',
			),
			'ftp_ssl' => array(
				'title' => $this->l('SSL Activado'),
				'type' => 'text',
			),
		);

		$helper = new HelperList();
		$helper->shopLinkType = '';
		$helper->simple_header = true;
		$helper->identifier = 'id_conn';
		$helper->actions = array('delete');
		$helper->show_toolbar = true;

		$helper->title = $this->l('Conexión FTP al servidor del proveedor');
		$helper->table = $this->name;
		$helper->token = Tools::getAdminTokenLite('AdminModules');
		$helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;
		$conn = $this->getConn();
		if (is_array($conn) && count($conn))
			return $helper->generateList($conn, $fields_list);
		else
			return false;
	}

	public function renderForm()
	{
		$fields_form_1 = array(
			'form' => array(
				'legend' => array(
					'title' => $this->l('Parametros de configuración FTP'),
					'icon' => 'icon-cloud-download'
				),
				'input' => array(
					array(
						'type' => 'hidden',
						'name' => 'id_conn',
					),
					array(
						'type' => 'text',
						'label' => $this->l('Nombre de la conexión FTP'),
						'name' => 'ftp_name',
						'lang' => false,
					),
					array(
						'type' => 'text',
						'label' => $this->l('Servidor FTP'),
						'name' => 'ftp_srv',
						'lang' => false,
						'required' => true,
					),
					array(
						'type' => 'text',
						'label' => $this->l('Usurio'),
						'name' => 'ftp_user',
						'lang' => false,
						'required' => true,
					),
					array(
						'type' => 'password',
						'label' => $this->l('Password'),
						'name' => 'ftp_pass',
						'required' => true,
					),
					array(
						'col' => 2,
		                'type' => 'text',
		                'prefix' => '<i class="icon icon-envelope"></i>',
		                'desc' => $this->l('Si lo dejas en blanco, las notificaciones se enviarán al administrador'),
		                'label' => $this->l('Email'),
		                'name' => 'ftp_mail',
					),
					array(
						'type' => 'switch',
						'label' => $this->l('Activar SSL'),
						'name' => 'ftp_ssl',
						'is_bool' => true,
						'values' => array(
							array(
								'id' => 'active_on',
								'value' => 1,
								'label' => $this->l('Activado')
							),
							array(
								'id' => 'active_off',
								'value' => 0,
								'label' => $this->l('Desactivado')
							)
						),
					),

				),
				'reset' => array(
					'title' => $this->l('LIMPIAR'),
					'class' => 'btn btn-danger pull-right',
					'icon' 	=> 'process-icon-cancel', 
					'name' 	=> 'resetFtp',
				),
				'submit' => array(
					'title' => $this->l('Guardar'),
					'class' => 'btn btn-primary pull-right',
					'name' => 'submitFtp',
				),
			),
		);
		$fields_form_2 = array(
			'form' => array(
				'legend' => array(
					'title' => $this->l('Parametros de configuración del CSV'),
					'icon' => 'icon-cog'
				),
				'input' => array(
					array(
						'col' => 1,
						'type' => 'text',
						'label' => $this->l('Archivo CSV Remoto'),
						'name' => 'csv_remote',
						'lang' => false,
						'required' => true,
					),
					array(
						'col' => 1,
						'type' => 'text',
						'label' => $this->l('CSV "Offset"'),
						'name' => 'csv_offset',
						'lang' => false,
						'required' => true,
					),
					array(
						'col' => 1,
						'type' => 'text',
						'label' => $this->l('CSV "Referencia"'),
						'name' => 'csv_reference',
						'lang' => false,
						'required' => true,
					),
					array(
						'col' => 1,
						'type' => 'text',
						'label' => $this->l('CSV "LPI"'),
						'name' => 'csv_lpi',
						'lang' => false,
						'required' => true,
					),
					array(
						'col' => 1,
						'type' => 'text',
						'label' => $this->l('CSV "Precio"'),
						'name' => 'csv_price',
						'lang' => false,
						'required' => true,
					),
				),
				'reset' => array(
					'title' => $this->l('LIMPIAR'),
					'class' => 'btn btn-danger pull-right',
					'icon' 	=> 'process-icon-cancel', 
					'name' 	=> 'resetCsv',
				),
				'submit' => array(
					'title' => $this->l('Guardar'),
					'class' => 'btn btn-primary pull-right',
					'name' => 'submitCsv',
				),
			),
		);

		$helper = new HelperForm();
		$helper->show_toolbar = false;
		$helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
		$this->fields_form = array();

		$helper->identifier = 'id_conn';
		$helper->submit_action = 'submitUpdate';
		$helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false).'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
		$helper->token = Tools::getAdminTokenLite('AdminModules');

		$helper->tpl_vars = array(
			'fields_value' => $this->getConfigFieldsValues(),
		);

		$conn = $this->getConn();
		if (is_array($conn) && count($conn))
			return $helper->generateForm(array($fields_form_1, $fields_form_2));
		else
			return $helper->generateForm(array($fields_form_1));

	}

	public function getConfigFieldsValues()
	{
		$fields_values = array(
			'id_conn' => '',
			'ftp_name' => '',
			'ftp_srv' => '',
			'ftp_user' => '',
			'csv_remote' => '',
			'csv_offset' => '',
			'csv_reference' => '',
			'csv_lpi' => '',
			'csv_price' => '',
			'ftp_mail' => '',
			'ftp_pass' => '',
			'ftp_ssl' => '',
		);

		return $fields_values;
	}

	/**
	 * [log description]
	 * @param  [type] $message [description]
	 * @return [type]          [description]
	 */
	public function log($message)
    {
        return '['.date('r')."]\t".$message.PHP_EOL;
    }

	/**
     * Start background import process
     *
     */
    public function init()
    {
    	$token = $this->csv->getTokenizer();
    	$control = Tools::getValue('token');
    	$result = '';
		
		if($token != $control)
		{
			die('No deberías estar intentando esto');
		}

		if(!$this->checkTask())
		{
			$this->finishTask();
		}

        $result .= $this->log('Comenzando la tarea de actualización');
		
		if(!$this->csv->downloadCsv())
		{	
			$result .= $this->log('error al descargar el archivo del ftp');
			die('downloadCSV');
		}
		
		$result .= $this->log('Archivo "' . Configuration::get('JMR_PU_REMOTE_FILE') . '" descargado:  OK ');

		if(!$this->procesarCsv())
		{	
			$result .= $this->log($this->message);
			die('ProcesarCSV');
		}
		
		$result .= $this->log($this->message);
		
		if(!$this->copyProducts())
		{	
			$result .= $this->log($this->message);
			die();
		}
		$result .= $this->log($this->message);

		if(!$this->compareUpdateProduct())
		{
			$result .= $this->log($this->message);
			die();
		}
		$result .= $this->log($this->message);

		if(!$this->compareUpdateAttribute())
		{
			$result .= $this->log($this->message);
			die();
		}
		$result .= $this->log($this->message);

		if(!$this->finishTask()) {
			$result .= $this->log($this->message);
		}

		$this->setProcessDate();
		
		$result .= $this->log($this->message);
		
		PriceUpdateMail::message($result);
		PriceUpdateMail::send();

		return $result;

    }

    private function setProcessDate()
    {
    	$timestamp = date('Y-m-d G:i:s');
    	Configuration::updateValue('JMR_PU_LASTUPDATE', $timestamp);
    }

    private function getProcessDate()
    {
    	$timestamp = Configuration::get('JMR_PU_LASTUPDATE');
    	return $timestamp;
    }

    private function checkTask()
    {    	
    	$last = $this->getProcessDate();
    	$today = date('Y-m-d G:i:s');

    	$sql = 'SELECT COUNT(*) FROM '._DB_PREFIX_.'pedregosa_master';
		$check = Db::getInstance()->getValue($sql);

		if($check > 0) {
    		return false;
    	}

    	if($last > $today) {
    		return false;
    	}
    	return true;
    }

    private function finishTask()
    {
    	$message;   	
    	// Vaciamos las tablas luego de la actualización o de un error.
    	$sql = array();
    	$sql[] = 'TRUNCATE TABLE '._DB_PREFIX_.'pedregosa_master';
    	$sql[] = 'TRUNCATE TABLE '._DB_PREFIX_.'pedregosa_slave';

    	foreach ($sql as $query) {
		    if (!Db::getInstance()->execute($query)) {
		    	$this->message = 'Ha ocurrido un error al vaciar las tablas';
		    	return $this->message;
		    	die();
		    }
		}
		$this->message = 'Tablas vaciadas correctamente: OK';
		return $this->message;
    }

    private function procesarCsv()
    {
		$message;
		
		$reference = (int) Configuration::get('JMR_PU_REFERENCE');
		$lpi = (int) Configuration::get('JMR_PU_LPI');
		$price = (int) Configuration::get('JMR_PU_PRICE');
		//$stock = (int) Configuration::get('JMR_PU_STOCK');
		
		$sql = $this->csv->prepareMasterCsv();

    	if(!$sql)
    	{
    		$this->message = 'Ha ocurrido un error inesperado';
    		return $this->message;
    		die('prepareMasterCsv');
    	}
		foreach($sql->getRecords() as $data)
	    {
	       $master[] = array(
				'reference' => $data[$reference],
				'lpi' => $a = str_replace(',', '.', $data[$lpi]),
				'price' => $b = str_replace(',', '.', $data[$price]),
				'total_price' => $a + $b + (($a + $b) / 10),
				//'stock' => $data[$stock],
			);
		}

	    if(!Db::getInstance()->insert('pedregosa_master', $master))
        {
        	$this->message = 'No se han insertado datos en la BBDD';
        	return $this->message;
        }

	    $this->message = 'CSV Master insertado correctamente en la BBDD';
	    return $this->message;
    }

    private function copyProducts()
    {
		$sql = 'SELECT * FROM '._DB_PREFIX_.'product WHERE reference <> \'\'';

		if(!$results = Db::getInstance()->ExecuteS($sql, true, false))
		{
			$this->message = 'Ha ocurrido un error exportando los productos';
			return $this->message;
		}
		foreach ($results as $row) 
		{
			$products[] = array(
				'id_slave' => $row['id_product'],
				'reference' => $row['reference'],
				'price' => $row['price'],
			);
		}
		//Ingresamos el array en la bbdd para su posterior comparación
		if(!Db::getInstance()->insert('pedregosa_slave', $products, false, false))
        {
        	$this->message = 'No se han insertado datos en la BBDD';
        	return $this->message;
        }

	    $this->message = 'Datos para comparar insertados correctamente en la BBDD:  OK ';
	    return $this->message;
    }

    private function compareUpdateProduct()
    {
    	$message;
		// Actualizamos los productos simples
		$sql = 'SELECT A.id_product, A.reference, B.total_price FROM '._DB_PREFIX_.'product A
				JOIN '._DB_PREFIX_.'pedregosa_master B ON A.reference=B.reference WHERE A.price <> B.total_price and A.reference <> \'\'';

		if(!$results = Db::getInstance()->executeS($sql))
		{
			$this->message = 'Ha ocurrido un error al intentar realizar la comparación de produtos';
			return $this->message;
			die();
		}
		foreach ($results as $row) 
		{			
			$updateP = 'UPDATE '._DB_PREFIX_.'product SET price = \''.$row['total_price'].'\' WHERE id_product = \''.$row['id_product'].'\'';
			
			if(!Db::getInstance()->execute($updateP))
			{
				$this->message = 'Ha ocurrido un error al actualizar los productos';
				return $this->message;
				die();
			}

			$updatePs = 'UPDATE '._DB_PREFIX_.'product_shop SET price = \''.$row['total_price'].'\' WHERE id_product = \''.$row['id_product'].'\'';

			if(!Db::getInstance()->execute($updatePs))
			{
				$this->message = 'Ha ocurrido un error al actualizar los productos';
				return $this->message;
				die();
			}
		}

		$this->message = 'Actualización de productos realizada con exito: OK';
		return $this->message;
    }

    private function compareUpdateAttribute()
    {
    	$message;

		$sql = 'SELECT A.id_product_attribute, A.id_product, A.reference, B.total_price FROM '._DB_PREFIX_.'product_attribute A
				JOIN '._DB_PREFIX_.'pedregosa_master B ON A.reference=B.reference WHERE A.price <> B.total_price';

		if(!$results = Db::getInstance()->executeS($sql))
		{
			$this->message = 'Ha ocurrido un error al intentar realizar la comparación de produtos combinados';
			return $this->message;
			die();
		}

		foreach ($results as $row) 
		{			
			$updatePa = 'UPDATE '._DB_PREFIX_.'product_attribute SET price = \''.$row['total_price'].'\' 
							WHERE id_product = \''.$row['id_product'].'\' AND id_product_attribute = \''.$row['id_product_attribute'].'\'';

			if(!Db::getInstance()->execute($updatePa))
			{
				$this->message = 'Ha ocurrido un error al actualizar los productos combinados';
				return $this->message;
				die();
			}

			$updatePas = 'UPDATE '._DB_PREFIX_.'product_attribute_shop SET price = \''.$row['total_price'].'\' 
							WHERE id_product = \''.$row['id_product'].'\' AND id_product_attribute = \''.$row['id_product_attribute'].'\'';

			if(!Db::getInstance()->execute($updatePas))
			{
				$this->message = 'Ha ocurrido un error al actualizar los productos combinados';
				return $this->message;
				die();
			}
		}

		$this->message = 'Actualización de productos realizada con exito: OK';
		return $this->message;
    }
}







