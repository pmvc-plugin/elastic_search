<?php
namespace PMVC\PlugIn\elastic_search;
class server 
{

    public $scheme;
    public $host;
    public $port;
    public $auth;

    public function getIndexList()
    {

    }

    public function getPort()
    {
        $es = \PMVC\plug('elastic_search');
        if (is_numeric($es['port'])) {
            return ':'.$es['port'];
        } else {
            return '';
        }
    }

    public function request($path,$params=array())
    {
        $respond = null;
        $url = $this->scheme.'://'.$this->host.$this->getPort().$path;
        $params = \PMVC\array_merge (
            array(
                CURLOPT_USERPWD=>$this->auth 
            ),
            $params
        );
        $curl  = \PMVC\plug('curl');
        $curl->get($url,function($r) use (&$respond, $url){
            if (200 === $r->code) {
               $respond = json_decode($r->body);
            } else {
                trigger_error('Get result error. Error Code:'.$r->code. ' url:'.$url);
            }
        })->set($params);
        $curl->run();
        return $respond;
    }

}
