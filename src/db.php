<?php
namespace PMVC\PlugIn\elastic_search;
class db 
{
    protected $index;
    protected $type;
    protected $path;

    function getAll()
    {
        $this->path='_search?q=*:*';
        $return =  $this->request();
        return (object)array(
            'hits'=>$return->hits->hits,
            'total'=>$return->hits->total
        );
    }

    function getPath()
    {
        return '/'.$this->index.'/'.$this->type.'/'.$this->path;
    }

    function getCommand()
    {

    }

    function request()
    {
        $es = \PMVC\plug('elastic_search');
        $server = $es->getDefaultServer();
        return $server->request(
            $this->getPath()
            ,$this->getCommand()
        );
    }

}
