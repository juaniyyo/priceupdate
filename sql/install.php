<?php
/**
 * 2018 1024Mbits.com
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author    1024Mbits.com <soporte@1024mbits.com>
 * @copyright 2018 1024Mbits.com
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

$sql = array();

$sql[] = 'CREATE TABLE IF NOT EXISTS ' . _DB_PREFIX_ . 'pedregosa_config (
        `id_conn` int(10) NOT NULL AUTO_INCREMENT,
        `ftp_name` varchar(254) NOT NULL,
        `ftp_srv` varchar(55) NOT NULL,
        `ftp_user` varchar(55) NOT NULL,
        `ftp_mail` varchar(55) NOT NULL,
        `ftp_pass` varchar(55) NOT NULL,
        `ftp_ssl` int(1) NOT NULL,
        PRIMARY KEY(`id_conn`))
        ENGINE='._MYSQL_ENGINE_.' default CHARSET=utf8';

$sql[] = 'CREATE TABLE IF NOT EXISTS ' . _DB_PREFIX_ . 'pedregosa_master (
        `id_master` int NOT NULL AUTO_INCREMENT,
        `reference`varchar(255) NOT NULL,
        `price` DECIMAL(20,6) NOT NULL,
        PRIMARY KEY(`id_master`))
        ENGINE='._MYSQL_ENGINE_.' default CHARSET=utf8';

$sql[] = 'CREATE TABLE IF NOT EXISTS ' . _DB_PREFIX_ . 'pedregosa_slave (
        `id_slave` int NOT NULL AUTO_INCREMENT,
        `reference`varchar(255) NOT NULL,
        `price` DECIMAL(20,6) NOT NULL,
        PRIMARY KEY(`id_slave`))
        ENGINE='._MYSQL_ENGINE_.' default CHARSET=utf8';

$sql[] = 'CREATE TABLE IF NOT EXISTS ' . _DB_PREFIX_ . 'pedregosa_final (
        `id_final` int NOT NULL AUTO_INCREMENT,
        `reference`varchar(255) NOT NULL,
        `price` DECIMAL(20,6) NOT NULL,
        PRIMARY KEY(`id_final`))
        ENGINE='._MYSQL_ENGINE_.' default CHARSET=utf8';

foreach ($sql as $query) {
    if (Db::getInstance()->execute($query) == false) {
        return false;
    }
}