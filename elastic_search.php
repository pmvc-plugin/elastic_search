<?php
namespace PMVC\PlugIn\elastic_search;
${_INIT_CONFIG}[_CLASS] = __NAMESPACE__.'\elastic_search';

\PMVC\l(__DIR__.'/src/db.php');
\PMVC\l(__DIR__.'/src/server.php');

class elastic_search extends \PMVC\PlugIn
{
    private $servers = array();

    public function init()
    {
    }

    public function getDb($key)
    {
       $path = \PMVC\lastSlash($this['dbs']).$key.'.php';
       if(\PMVC\realpath($path)){
            \PMVC\l($path);
            $class = __NAMESPACE__.'\\'.$key;
            if(class_exists($class)){
                return new $class;
            } else {
                trigger_error($class .' not exists.');
            }
       } else {
            trigger_error($path .' not exists.');
       }
    }

    public function getDefaultServer()
    {
        if (empty($this->servers[''])) {
            $server = new server(); 
            $server->scheme = $this['SCHEME'];
            $server->host = $this['HOST'];
            $server->port = $this['PORT'];
            $server->auth = $this['AUTH'];
            $this->servers[''] = $server;
        }
        return $this->servers[''];
    }



}
