<?php
/**
 * @package smvc
 * @copyright Copyright (c) 2010, Yoann Mikami
 */

/**
 * Init plugin which provides a MongoDB connector
 */
final class Plugin_Init_MongoDB implements Iface_Plugin_Init
{
    /**
     * Connects to MongoDB using specified configuration
     *
     * Configuration file:
     *   mongodb->host [Optional, default='localhost'] host on which MongoDB instance is running
     *   mongodb->port [Optional, default=27017] port on which MongoDB is bound
     *   mongodb->user [Optional, default=none] User used to connect to the database
     *   mongodb->password [Optional, default=none] password matching the specified user during connection
     *   mongodb->name [Optional, default='default'] Database name to connect to
     *
     * @param Utils_Config $config the configuration settings
     * @param string $namespace the application namespace
     * @param string $request the requested page
     * @post The mongoDB admin object is registered in the Zend_Registry as 'mongo_db_admin'
     * @post The mongoDB connection to the database is registered in the Zend_Registry as 'mongo_db'
     */
    public function process(Utils_Config $config, $namespace, $request)
    {
        $host = (isset($config->mongoDB->host)?$config->mongoDB->host:'localhost');
        $port = (isset($config->mongoDB->port)?$config->mongoDB->port:27017);
        $name = (isset($config->mongoDB->name)?$config->mongoDB->name:'default');
        $db = new Mongo("mongodb://$host:$port");
        Zend_Registry::set('mongo_db_admin', $db);
        Zend_Registry::set('mongo_db', $db->$name);
    }
}
