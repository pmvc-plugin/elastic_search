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
        $path = '/_aliases';
        $return = $this->request($path);
        if (empty($return->body)) {
            trigger_error('no index found in this server: '.$this->getBaseUrl());
            return;
        }
        $arr = array();
        foreach ($return->body as $k=>$v) {
             $arr[]=$k;
        }
        return $arr;
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

    public function getBaseUrl()
    {
        return $this->scheme.'://'.$this->host.$this->getPort();
    }

    public function request($path,$params=array())
    {
        $respond = null;
        $url = $this->getBaseUrl().$path;
        $params = \PMVC\array_merge (
            array(
                CURLOPT_USERPWD=>$this->auth 
            ),
            $params
        );
        $curl  = \PMVC\plug('curl');
        $curl->get($url,function($r) use (&$respond, $url){
            if (400 >= $r->code) {
               $respond = (object)array(
                'header'=>$r->header,
                'body'=>json_decode($r->body)
               );
            } else {
                trigger_error('Get result error. Error Code:'.$r->code. ' url: '.$url);
            }
        })->set($params);
        $curl->run();
        return $respond;
    }

}
