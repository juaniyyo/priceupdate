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
if (!defined('_PS_VERSION_'))
	exit;

require_once(dirname(__FILE__) . '/vendor/autoload.php');
require_once _PS_MODULE_DIR_.'/priceupdate/classes/PriceUpdateCommon.php';


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
		$this->env = new Dotenv\Dotenv(__DIR__);
		$this->env->load();
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
		if (!parent::uninstall() 
		|| include(dirname(__FILE__) . '/sql/uninstall.php')
		)
			return false;

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
			$sql = 'SELECT b.`id_conn`, b.`ftp_name`, b.`ftp_srv`, b.`ftp_user`, b.`ftp_pass`, b.`ftp_ssl` FROM `'._DB_PREFIX_.'pedregosa_config` b WHERE b.id_conn='.(int)$id;

			if (!$results = Db::getInstance()->getRow($sql))
			{
				return false;
			}

			$link['id_conn'] = (int)$results['id_conn'];
			$link['ftp_name'] = $results['ftp_name'];
			$link['ftp_srv'] = $results['ftp_srv'];
			$link['ftp_user'] = $result['ftp_user'];
			$link['ftp_pass'] = $result['ftp_pass'];
			$link['ftp_ssl'] = $result['ftp_ssl'];

			return $link;
		}

		return false;
	}

	public function getConn()
	{
		$result = array();

		$sql = 'SELECT b.`id_conn`, b.`ftp_name`, b.`ftp_srv`, b.`ftp_user`, b.`ftp_pass`, b.`ftp_ssl` FROM `'._DB_PREFIX_.'pedregosa_config` b ORDER BY `id_conn` DESC';

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
			$result[$i]['ftp_pass'] = $link['ftp_pass'];
			$result[$i]['ftp_ssl'] = $link['ftp_ssl'];

			$i++;
		}

		return $result;
	}

	public function addConn()
	{
		if (!Db::getInstance()->execute('INSERT INTO '._DB_PREFIX_.'pedregosa_config (`id_conn`, `ftp_name`, `ftp_srv`, `ftp_user`, `ftp_pass`, `ftp_ssl`)
			VALUES (NULL, \''.pSQL(Tools::getValue('ftp_name')).'\', \''.pSQL(Tools::getValue('ftp_srv')).'\', \''.pSQL(Tools::getValue('ftp_user')).'\', \''.pSQL(Tools::getValue('ftp_pass')).'\', '.((isset($_POST['ftp_ssl']) && Tools::getValue('ftp_ssl')) == 'on' ? 1 : 0).')') 
		|| !$id_link = Db::getInstance()->Insert_ID())
		{ 
			return false; 
		}

		return true;
	}

	public function deleteConn()
	{
		return Db::getInstance()->execute('DELETE FROM '._DB_PREFIX_.'pedregosa_config WHERE `id_conn` = '.(int)Tools::getValue('id_conn'));
	}

	public function getContent()
	{
		$this->_html = '';

		$cron_command = $this->csv->getCronCommand();
        $php_dir = $this->csv->getPHPExecutableFromPath();

		$this->_html .= '<div class="panel panel-success">';
		$this->_html .= '<div class="panel-heading"> Configuración de tarea cron </div>';
		$this->_html .= '<div class="alert alert-info"><p>Copia y pega este comando en la <b>lista de tareas cron</b> del servidor</p>';
		$this->_html .= '<pre>' . $php_dir . ' ' . $cron_command . '</pre>';
		$this->_html .= '</div>';
		$this->_html .= '</div>';

		// Añadir conexion FTP
		if (Tools::isSubmit('submitFtp'))
		{
			if (empty($_POST['ftp_name']) || empty($_POST['ftp_srv']) || empty($_POST['ftp_user']) || empty($_POST['ftp_pass']))
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
			'id_conn' => array(
				'title' => $this->l('FTP conn ID'),
				'type' => 'text',
			),
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
					),
					array(
						'type' => 'text',
						'label' => $this->l('Usurio'),
						'name' => 'ftp_user',
						'lang' => false,
					),
					array(
						'type' => 'password',
						'label' => $this->l('Password'),
						'name' => 'ftp_pass',
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
				)
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
		
		return $helper->generateForm(array($fields_form_1));
	}

	public function getConfigFieldsValues()
	{
		$fields_values = array(
			'id_conn' => '',
			'ftp_name' => '',
			'ftp_srv' => '',
			'ftp_user' => '',
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
		
		if($token != $control)
		{
			die('No deberías estar intentando esto');
		}

		if(!$this->checkTask())
		{
			$this->finishTask();
		}

    	$result = '';
        
        $result .= $this->log('Comienza la tarea');
        
        $result .= $this->log('Conectando a: "' . getenv('FTP_HOST') . '"  OK ');
		
		if(!$this->csv->downloadCsv())
		{	//Mensaje de error
			$result .= $this->log('error al descargar el archivo del ftp: "' . getenv('FTP_HOST') . '"');
			die();
		}
		// Mensaje OK
		$result .= $this->log('Archivo"' . getenv('REMOTE_FILE') . '" descargado:  OK ');

		if(!$this->procesarCsv())
		{	// Mensaje de error
			$result .= $this->log($this->message);
			die();
		}
		// Mensaje OK
		$result .= $this->log('Procesando CSV descargado:  OK ');
		
		if(!$this->copyProducts())
		{	// Mensaje de error
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

		if($this->finishTask())
		{
			//Mensaje final tarea terminada OK
			$result .= $this->log('Tarea terminada correctamente: OK ');
			//TODO Enviar correo electronico avisando.
		}

		return $result;

    }

    private function setProcessDate()
    {}

    private function getProcessDate()
    {}

    private function checkTask()
    {}

    private function finishTask()
    {
    	/*Mail::Send((int)(Configuration::get('PS_LANG_DEFAULT')), // defaut language id
        'contact', // email template file to be use
        $this->displayName.' Module Installation', // email subject
        array(
          '{email}' => 'soporte@1024mbits.com', // sender email address
          '{message}' => $this->displayName.' has been installed on:'._PS_BASE_URL_.__PS_BASE_URI__ // email content
        ), 
        'your-email@mail.com', // receiver email address 
        NULL, NULL, NULL);
        */
    }

    private function procesarCsv()
    {
    	$message;

    	$sql = $this->csv->prepareMasterCsv();
    	
    	if(!$sql)
    	{
    		$this->message = 'Ha ocurrido un error inesperado';
    		return $this->message;
    		die();
    	}
    	foreach($sql->fetchPairs(3, 5) as $reference => $price) 
	    {
	        $master[] = array(
	            'reference' => $reference,
	            'price' => str_replace(',', '.', $price),
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
		$sql = 'SELECT A.id_product, A.reference, B.price FROM '._DB_PREFIX_.'product A
				INNER JOIN '._DB_PREFIX_.'pedregosa_master B ON A.reference=B.reference WHERE A.price <> B.price and A.reference <> \'\'';

		if(!$results = Db::getInstance()->executeS($sql))
		{
			$this->message = 'Ha ocurrido un error al intentar realizar la comparación de produtos';
			return $this->message;
			die();
		}

		foreach ($results as $row) 
		{			
			$updateP = 'UPDATE '._DB_PREFIX_.'product SET price = \''.$row['price'].'\' WHERE id_product = \''.$row['id_product'].'\'';
			
			if(!Db::getInstance()->execute($updateP))
			{
				$this->message = 'Ha ocurrido un error al actualizar los productos';
				return $this->message;
				die();
			}

			$updatePs = 'UPDATE '._DB_PREFIX_.'product_shop SET price = \''.$row['price'].'\' WHERE id_product = \''.$row['id_product'].'\'';

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

    /*public function compareUpdateAttribute()
    {
    	$message;
		// Actualizamos los productos simples
		$sql = 'SELECT A.id_product_attribute, A.id_product, A.reference, B.price FROM '._DB_PREFIX_.'product_attribute A
				INNER JOIN '._DB_PREFIX_.'pedregosa_master B ON A.reference=B.reference WHERE A.price <> B.price';

		if(!$results = Db::getInstance()->executeS($sql))
		{
			$this->message = 'Ha ocurrido un error al intentar realizar la comparación de produtos';
			return $this->message;
			die();
		}

		foreach ($results as $row) 
		{			
			$updatePa = 'UPDATE '._DB_PREFIX_.'product_attribute SET price = \''.$row['price'].'\' 
							WHERE id_product = \''.$row['id_product'].'\' AND id_product_attribute = \''.$row['id_product_attribute'].'\'';

			if(!Db::getInstance()->execute($updatePa))
			{
				$this->message = 'Ha ocurrido un error al actualizar los productos';
				return $this->message;
				die();
			}

			$updatePas = 'UPDATE '._DB_PREFIX_.'product_attribute_shop SET price = \''.$row['price'].'\' 
							WHERE id_product = \''.$row['id_product'].'\' AND id_product_attribute = \''.$row['id_product_attribute'].'\'';

			if(!Db::getInstance()->execute($updatePas))
			{
				$this->message = 'Ha ocurrido un error al actualizar los productos';
				return $this->message;
				die();
			}
		}

		$this->message = 'Actualización de productos realizada con exito: OK';
		return $this->message;
    }*/
}







