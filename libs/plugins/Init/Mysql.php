<?php
/**
 * @package smvc
 * @copyright Copyright (c) 2010, Yoann Mikami
 */

/**
 * Init plugin which provides a MySQL connector
 */
final class Plugin_Init_Mysql implements Iface_Plugin_Init
{
    /**
     * Connects to MySQL using specified configuration
     *
     * Configuration file:
     *   mysql->host [Optional, default='localhost'] host on which MySQL instance is running
     *   mysql->user [Optional, default=''] User used to connect to the database
     *   mysql->password [Optional, default=''] password matching the specified user during connection
     *   mysql->name [Optional, default='default'] Database name to connect to
     *
     * @param Utils_Config $config the configuration settings
     * @param string $namespace the application namespace
     * @param string $request the requested page
     * @post The MySQL connection to the database is registered in the Zend_Registry as 'mysql_db'
     */
    public function process(Utils_Config $config, $namespace, $request)
    {
        $host = (isset($config->mysql->host)?$config->mysql->host:'localhost');
        $name = (isset($config->mysql->name)?$config->mysql->name:'default');
        $user = (isset($config->mysql->user)?$config->mysql->user:'');
        $pass = (isset($config->mysql->pass)?$config->mysql->pass:'');
        $db = Zend_Db::factory('Mysqli',
                     array('host' => $host,
                           'username' => $user,
                           'password' => $pass,
                           'dbname'   => $name));
        $db->query('SET NAMES UTF8');
        Zend_Registry::set('mysql_db', $db);
    }
}
