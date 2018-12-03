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

use League\Csv\Reader;
use League\Csv\Statement;

/**
 * 
 */
class PriceUpdateCommon
{
	public $ftp;
	public $module;
	public $token;

	private $download_folder;
    private $remote_file;
    private $local_file;
    private $ps_product;

	public function __construct()
	{
		$this->ftp = new \FtpClient\FtpClient();

		$this->download_folder = getenv('DOWNLOAD_FOLDER');
	    $this->remote_file = getenv('REMOTE_FILE');
	    $this->local_file = _PS_MODULE_DIR_ . 'priceupdate' . DIRECTORY_SEPARATOR . $this->download_folder . DIRECTORY_SEPARATOR . getenv('LOCAL_FILE');
	    $this->ps_product = _PS_MODULE_DIR_ . 'priceupdate' . DIRECTORY_SEPARATOR . $this->download_folder . DIRECTORY_SEPARATOR . getenv('PS_PRODUCT');
	}

	public function getTokenizer()
    {
    	$token = Tools::getAdminToken('AdminModules');

    	return $token;
    }

	/**
	 * [downloadCsv description]
	 * @return [type] [description]
	 */
	public function downloadCsv()
	{
        $host;
        $user;
        $pass;
        $ssl;

        $sql = 'SELECT `ftp_srv`, `ftp_user`, `ftp_pass`, `ftp_ssl` FROM `'._DB_PREFIX_.'pedregosa_config`';

        if (!$datas = Db::getInstance()->getRow($sql))
        {
            return false;
        }

        $this->host = $datas['ftp_srv'];
        $this->user = $datas['ftp_user'];
        $this->pass = $datas['ftp_pass'];
        $this->ssl  = $datas['ftp_ssl'];

        /**
		 * Conectamos al FTP
		 */
        $this->ftp->connect($this->host);
		if(!$this->ftp->login($this->user, $this->pass))
		{
			return false;
		}

		/**
		 * Descargamos el fichero CSV para actualizar
		 */
        if(!$this->ftp->get($this->local_file, $this->remote_file, FTP_ASCII)) {
            return false;
        }
        return true;
	}


	/**
	 * [masterSortById description]
	 * @param  [type] $a [description]
	 * @param  [type] $b [description]
	 * @return [type]    [description]
	 */
	public function masterSortById($a, $b)
	{
    	return $a[3] - $b[3];
	}

    /**
     * [prepareMasterCsv description]
     * @return [type] [description]
     */
    public function prepareMasterCsv()
    {
    	try 
    	{
		    $master = Reader::createFromPath($this->local_file, 'r');
		    $master->setDelimiter(';');

		    $stmt = ( new Statement() )->offset(2);
		    $sql = $stmt->process($master);

		    return $sql;

		} catch (Exception $e) 
		{
		    echo 'Excepción capturada para el CSV Master: ',  $e->getMessage(), "\n";
		}
    }

    public function getCronCommand()
    {
    	$token = $this->getTokenizer();

        $result = '"'._PS_ROOT_DIR_.DIRECTORY_SEPARATOR.'index.php';
        $result .= '?fc=module&module=priceupdate&controller=cron&token='.$token.'"';
         
        return $result;
    }

    /**
     * return running php path
     * @see http://stackoverflow.com/a/3889630
     *
     * @return string
     */
    public function getPHPExecutableFromPath()
    {
        $paths = explode(PATH_SEPARATOR, getenv('PATH'));
        try {
            foreach ($paths as $path) {
            // we need this for XAMPP (Windows)
                if (strstr($path, 'php.exe')
                    && isset($_SERVER['WINDIR'])
                    && file_exists($path) && is_file($path)
                ) {
                    return $path;
                } else {
                    $php_executable = $path.DIRECTORY_SEPARATOR.'php'.(isset($_SERVER['WINDIR']) ? '.exe' : '');
                    if (file_exists($php_executable)
                        && is_file($php_executable)) {
                        return $php_executable;
                    }

                    $php_executable = $path.DIRECTORY_SEPARATOR.'php5'.(isset($_SERVER['WINDIR']) ? '.exe' : '');
                    if (file_exists($php_executable)
                        && is_file($php_executable)) {
                        return $php_executable;
                    }
                }
            }
        } catch (Exception $e) {
            // not found
            return '/usr/bin/env php';
        }

        return '/usr/bin/env php'; // not found
    }
}









