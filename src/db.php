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

    /**
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/search-request-scroll.html
     */
    function getAll($size=null)
    {
        //<!-- Get scroll id 
        if(is_null($size)){
            $size = $this->getTotal();
        }
        $this->path='_search?search_type=scan&scroll=10m&size='.$size;
        $match_all = array(
            'query'=>array(
                'match_all'=> new \stdClass()
            )
        );
        $this->setCommand(null, $this->getPost($match_all));
        $scroll_id_return =  $this->request();
        $scroll_id = $scroll_id_return->body->_scroll_id;
        // Get scroll id -->

        //<!-- Get Data 
        $path='/_search/scroll?scroll=10m&scroll_id='.$scroll_id;
        $return =  $this->request($path);
        return (object)array(
            'hits'=>$return->body->hits->hits,
            'total'=>$return->body->hits->total
        );
        // Get Data --> 
    }

    function getTotal()
    {
        $this->path='_search?q=*:*&size=0';
        $return =  $this->request();
        return $return->body->hits->total;
    }

    function setPath($path)
    {
        $this->extra_path = $path;
    }

    function getPath()
    {
        return '/'.$this->index.'/'.$this->type.'/'.$this->path.$this->extra_path;
    }

    function setCommand($method=null, $params=array())
    {
        if (is_null($method)) {
            $method = 'GET';
        }
        $arr = array(
            CURLOPT_CUSTOMREQUEST=>$method
        );
        if (!empty($params)) {
            $arr = \PMVC\array_merge($arr,$params);
        }
        $this->command =& $arr;
    }

    /**
     * get Post curl array
     */
     public function getPost($v)
     {
        if (!is_string($v)) {
           $v = json_encode($v); 
        }
        return array (
           CURLOPT_POSTFIELDS => $v
        );
     }

    function getCommand()
    {
        return $this->command;
    }

    function reset()
    {
        $this->command = null;
        $this->path = null;
        $this->extra_path = null;
    }

    function request($path=null)
    {
        if (is_null($path)) {
            $path = $this->getPath();
        }
        $es = \PMVC\plug('elastic_search');
        $server = $es->getDefaultServer();
        $return =  $server->request(
            $path
            ,$this->getCommand()
        );
        $this->reset();
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

