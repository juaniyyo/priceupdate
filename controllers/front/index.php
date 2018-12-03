<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future. If you wish to customize this module for your
 * needs please refer to http://doc.prestashop.com/display/PS15/Overriding+default+behaviors
 * #Overridingdefaultbehaviors-Overridingamodule%27sbehavior for more information.
 *
 * @author    Samdha <contact@samdha.net>
 * @copyright Samdha
 * @license   commercial license see license.txt
 */

header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
header('Last-Modified: '.gmdate('D, d M Y H:i:s \G\M\T'));
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

/* find module name */
$module_name = '';
$directories = explode(DIRECTORY_SEPARATOR, dirname(__FILE__));
$index = array_search('modules', $directories);
if (($index !== false) && isset($directories[$index + 1]))
	$module_name = $directories[$index + 1];

/* redirect to documentation */
if ($module_name != '')
{
	$documentation_url = 'http://prestawiki.samdha.net/wiki/Module:'.$module_name;
	header('Location: '.$documentation_url);
}
else
	header('Location: ../');
