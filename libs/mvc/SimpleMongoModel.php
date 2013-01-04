<?php
/**
 * @package smvc
 * @copyright Copyright (c) 2010, Yoann Mikami
 */

/**
 * Defines a model implementation for the SimpleMVC which connects to a MongoDB DB
 *
 * @package SimpleMVC
 */
abstract class SimpleMongoModel implements Iface_Model
{
    public $_id = null;

    private $_db = null;
    private $_crits = array();
    private $_sort = array();


    final public function insert($data, $options=array())
    {
        $data = $this->hydrateData($data, 'insert');
        if ($this->_db) {
            unset($data['_id']);
            $data = $this->doInsert($data, $options);
            $this->mapData($data);
        }
        return $this;
    }

    final public function update($data, $options=array())
    {
        $data = $this->hydrateData($data, 'insert');
        if ($this->_db) {
            $ids = $this->getUniqueIds();
            $crits = array();
            foreach ($ids as $field) {
                $crits[$field] = isset($data[$field])?$data[$field]:$this->$field;
            }
            unset($data['_id']);
            $data = $this->doUpdate($crits, $data, $options);
            $this->mapData($data);
        }
        return $this;
    }

    final public function remove($crits=array(), $safe=true)
    {
        $ret = null;
        if ($this->_db) {
            $this->doRemove($crits, array('safe'=>$safe));
        }
        return $this;
    }

    final public function findOne($data=array())
    {
        $data = $this->hydrateFindCriteria($data);
        $ret = null;
        if ($this->_db) {
            $ret = $this->_db->findOne($data);
            if (null === $ret) {
                return false;
            }
            $this->mapData($ret);
        }
        return $this;
    }

    final public function find($data=array(), $sort=array(),
                               $limit=0, $offset=0)
    {
        $data = $this->hydrateFindCriteria($data);
        $ret = null;
        if ($this->_db) {
            $ret = $this->_db->find($data)
                             ->limit($limit)
                             ->skip($offset)
                             ->sort($sort);
        }
        $out = array();
        foreach ($ret as $r) {
            $m = $this->createFindInstance();
            $m->mapData($r);
            $out[] = $m;
        }
        return $out;
    }

    final public function count($data=array())
    {
        $ret = null;
        if ($this->_db) {
            $ret = $this->_db->count($data);
        }
        return $ret;
    }

    /* ---- Find/count and criteria/sort as separate methods ---- */

    final public function setFindCriteria($crits)
    {
        $this->_crits = $crits;
        return $this;
    }

    final public function setFindSort($sort)
    {
        $this->_sort = $sort;
        return $this;
    }

    final public function findSet($limit=0, $offset=0)
    {
        return $this->find($this->_crits, $this->_sort, $limit, $offset);
    }

    final public function countSet()
    {
        return $this->count($this->_crits);
    }


    final public function mapReduce(SimpleMongoModel $outClass,
                                    $m, $r, $f=null, $q=null, $sort=null)
    {
        $db = $this->_db->db;

        $cmd = array();

        $cmd['mapreduce'] = $this->getDbSection();
        $cmd['map'] = $m;
        $cmd['reduce'] = $r;
        if (!is_null($q)) {
            $cmd['query'] = $q;
        }
        if (!is_null($f)) {
            $cmd['finalize'] = $f;
        }

        $res = $db->command($cmd);
        return new Model_MapReduce($res, $outClass);
    }

    final public function setRef()
    {
        return $this->_db->createDBRef($this->getData());
    }

    final public function mapData($data)
    {
        $data = $this->hydrateData($data, 'map');
        foreach ($data as $pName => $val) {
            if ('_id' === $pName && is_object($val)) {
                $val = $val->__toString();
            }
            $this->$pName = $val;
        }
    }

    final public function getData()
    {
        $ret = array();
        $refl = new ReflectionClass($this);
        $props = $refl->getProperties(ReflectionProperty::IS_PUBLIC);
        foreach ($props as $prop) {
            $ret[$prop->getName()] = $prop->getValue($this);
        }
        return $ret;
    }


    public function __construct()
    {
        $db = Zend_Registry::get('mongo_db');
        if ($db) {
            $dbSection = $this->getDbSection();
            $this->_db = $db->$dbSection;
        }
        $this->dbInit();
        $this->init();
    }

    final protected function dbInit()
    {
        $this->doDbInit($this->_db);
    }

    final protected function init()
    {
        $this->doInit();
    }

    final protected function hydrateData($data, $type)
    {
        $out = array();
        $refl = new ReflectionClass($this);
        $props = $refl->getProperties(ReflectionProperty::IS_PUBLIC);
        foreach ($props as $prop) {
            $pName = $prop->getName();
            $out[$pName] = isset($data[$pName])?$data[$pName]:$this->$pName;
            if ('insert' === $type && '_id' === $pName && !empty($out[$pName])) {
                $out[$pName] = new MongoId($out[$pName]);
            }
        }
        $m = 'doHydrate'.ucfirst($type).'Data';
        if (method_exists($this, $m)) {
            $out = $this->$m($out);
        }
        return $out;
    }

    final protected function hydrateFindCriteria($data)
    {
        $data = $this->doHydrateFindCriteria($data);
        if (array_key_exists('_id', $data) && !is_object($data['_id'])) {
            $data['_id'] = new MongoId($data['_id']);
        }
        return $data;
    }

    final protected function getUniqueIds()
    {
        return $this->doGetUniqueIds();
    }

    // subclass can override :
    // - doHydrateInsertData($data) : before insert/update
    // - doHydrateFindCriteria($data) : before findOne/find
    // - doHydrateMapData($data) : after insert/update/find
    protected function doHydrateInsertData($data) { return $data; }
    protected function doHydrateFindCriteria($data) { return $data; }
    protected function doHydrateMapData($data) { return $data; }

    protected function doInsert($data, $opts)
    {
        try {
            $res = $this->_db->insert($data, $opts);
        } catch(MongoCursorException $e) {
            throw new WrappedException('Error while inserting data', $e); }
        return $data;
    }

    protected function doUpdate($crits, $data, $opts)
    {
        try {
            $res = $this->_db->update($crits, $data, $opts);
        } catch(MongoCursorException $e) {
            throw new WrappedException('Error while updating data', $e); }
        return $data;
    }

    protected function doRemove($crits, $opts)
    {
        try {
        return $this->_db->remove($crits, $opts);
        } catch(MongoCursorException $e) {
            throw new WrappedException('Error while removing data', $e); }
    }


    protected function doDbInit($db) {}
    protected function doInit() {}
    protected function createFindInstance() { $c = get_class($this); return new $c(); }
    protected function doGetUniqueIds() { return array('_id'); }

    abstract protected function getDbSection();
}
