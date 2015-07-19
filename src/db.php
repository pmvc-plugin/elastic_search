<?php
namespace PMVC\PlugIn\elastic_search;
class db implements \ArrayAccess
{
    protected $index;
    protected $type;
    protected $path;
    protected $extra_path;
    protected $command;
    protected $results;
    protected $last_result;

    function getAll()
    {
        $this->path='_search?q=*:*';
        $return =  $this->request();
        return (object)array(
            'hits'=>$return->body->hits->hits,
            'total'=>$return->body->hits->total
        );
    }

    function setPath($path)
    {
        $this->extra_path = $path;
    }

    function getPath()
    {
        return '/'.$this->index.'/'.$this->type.'/'.$this->path.$this->extra_path;
    }

    function setCommand($method, $params=array())
    {
        $arr = array(
            CURLOPT_CUSTOMREQUEST=>$method
        );
        if (!empty($params)) {
            $arr = \PMVC\array_merge($arr,$params);
        }
        $this->command =& $arr;
    }


    function getCommand()
    {
        return $this->command;
    }

    function request()
    {
        $es = \PMVC\plug('elastic_search');
        $server = $es->getDefaultServer();
        $return =  $server->request(
            $this->getPath()
            ,$this->getCommand()
        );
        $this->command = null;
        $this->path = null;
        $this->extra_path = null;
        return $return;
    }

    /**
     * Get
     *
     * @param mixed $k key
     *
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/master/docs-get.html
     * @return mixed 
     */
    public function &offsetGet($k=null)
    {
        $return = null;
        if(is_null($k)){
            return $return;
        }
        
        $this->path=$k;
        $return =  $this->request();
        if (isset($return->body)) {
            return $return->body;
        } else {
            return $return;
        }
    }

    /**
     * ContainsKey
     *
     * @param string $key key 
     *
     * @return boolean
     */
    public function offsetExists($k)
    {
        $this->path=$k.'?_source=false';
        $this->setCommand('HEAD');
        $return =  $this->request();
        return isset($return->header);
    }

    /**
     * get Post curl array
     */
     public function getPost($v)
     {
        return array (
           CURLOPT_POSTFIELDS => json_encode($v) 
        );
     }

    /**
     * Set 
     *
     * @param mixed $k key
     * @param mixed $v value 
     *
     * @return bool 
     */
    public function offsetSet($k=null, $v=null)
    {
        if (is_null($k) || !isset($this[$k])) {
            return $this->insert($k, $v);
        } else {
            return $this->update($k, $v);
        }
    }

    /**
     * insert
     */
     public function insert($k,$v)
     {
        if (is_null($k)) {
            $k = \PMVC\plug('guid')->gen();
        }
        $this->path=$k.'/_create';
        $this->setCommand('PUT', $this->getPost($v));
        $return =  $this->request();
        $this->setResult($return,$k);
        return $return;
     }

    /**
     * update 
     */
     public function update($k, $v, $replace=false)
     {
        if (is_null($k)) {
            trigger_error('Update command not contain a key');
        }
        if ($replace) {
            $this->path=$k;
            $arr = $v;
            $method = 'PUT';
        } else {
            $this->path=$k.'/_update';
            $arr = array(
                'doc' => $v
            );
            $method = 'POST';
        }
        $this->setCommand($method, $this->getPost($arr));
        $return =  $this->request();
        $this->setResult($return,$k);
        return $return;
     }

    /**
     * Clean
     *
     * @param mixed $k key
     *
     * @return bool 
     */
    public function offsetUnset($k=null)
    {
        $this->path=$k;
        $this->setCommand('DELETE');
        $return =  $this->request();
        $this->setResult($return,$k);
        return $return;
    }

    /**
     * Set Result
     */
     public function setResult($res,$k=null) {
        if(is_null($k)){
            $this->results[] = $res;        
        } else {
            $this->results[$k] = $res;        
        }
        $this->last_result = $res;
     }

    /**
     * get Result
     */
     public function getResult($k=null) 
     {
        $arr = array();
        if (!is_null($k)) {
            $arr['key'] = $this->results[$k];
        }
        $arr['last'] = $this->last_result;
        return (object)$arr;
     }
}

