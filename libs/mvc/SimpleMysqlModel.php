<?php
/**
 * @package smvc
 * @copyright Copyright (c) 2010, Yoann Mikami
 */

/**
 * Defines a model implementation for the SimpleMVC which connects to a MySQL DB
 *
 * @package SimpleMVC
 */
abstract class SimpleMysqlModel implements Iface_Model, Iface_MySQLAdapter
{
    public $_id = null;

    private $_db = null;
    private $_table = null;
    private $_crits = array();
    private $_sort = array();


    final public function insert($data, $options=null)
    {
        $data = $this->hydrateData($data, 'insert');
        if ($this->_db) {
            $ret = $this->doInsert($data, $options);
            $data['_id'] = $ret;
            $this->mapData($data);
        }
        return $this;
    }

    final public function update($data, $options=null)
    {
        $data = $this->hydrateData($data, 'update');
        if ($this->_db) {
            $_ids = $this->getUniqueIds();
            $crits = array();
            foreach ($_ids as $field) {
                $crits[$field . ' = ?'] = isset($data[$field])?$data[$field]:$this->$field;
            }
            unset($data['_id']);
            $ret = $this->doUpdate($crits, $data, $options);
            $this->mapData($data);
        }
        return $this;
    }

    final public function remove($data=array(), $options=null)
    {
        $ret = null;
        if ($this->_db) {
            $crits = array();
            foreach ($data as $k => $d) {
                $crits[$k . ' = ?'] = $d;
            }
            $this->doRemove($crits, $options);
        }
        return $this;
    }

    final public function findOne($data=array())
    {
        $crits = $this->hydrateFindCriteria($data);
        $ret = null;
        if ($this->_db) {
            $ret = $this->doFindOne($crits);
            if (false === $ret) {
                return false;
            }
            $this->mapData($ret);
        }
        return $this;
    }

    final public function find($data=array(), $sort=array(),
                               $limit=0, $offset=0)
    {
        $crits = $this->hydrateFindCriteria($data);
        $ret = null;
        if ($this->_db) {
            $ret = $this->doFind($crits, $sort, $limit, $offset);
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
            $ret = $this->doCount($data);
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


    final public function mapData($data)
    {
        $data = $this->hydrateData($data, 'map');
        foreach ($data as $pName => $val) {
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
        $db = Zend_Registry::get('mysql_db');
        if ($db) {
            $this->_table = $this->getTable();
            $this->_db = $db;
        }
        $this->init();
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
        }
        $m = 'doHydrate'.ucfirst($type).'Data';
        if (method_exists($this, $m)) {
            $out = $this->$m($out);
        }
        return $out;
    }

    final protected function hydrateFindCriteria($data)
    {
        return $this->doHydrateFindCriteria($data);
    }

    final protected function getUniqueIds()
    {
        return $this->doGetUniqueIds();
    }

    // subclass can override :
    // - doHydrateInsertData($data) : before insert
    // - doHydrateUpdateData($data) : before update
    // - doHydrateFindCriteria($data) : before findOne/find
    // - doHydrateMapData($data) : after insert/update/find
    protected function doHydrateInsertData($data) { return $data; }
    protected function doHydrateUpdateData($data) { return $data; }
    protected function doHydrateFindCriteria($data)
    {
        $crits = array();
        foreach ($data as $k => $d) {
            $op = '='; $val = $d;
            if (is_array($d)) {
                list($op, $val) = $d;
                if (is_array($val)) { $val = implode(',', $val); }
            }
            $crits[$k.$op.'?'] = $val;
        }
        return $crits;
    }
    protected function doHydrateMapData($data) { return $data; }

    protected function doInsert($data, $opts)
    {
        unset($data['_id']);
        try {
            $this->_db->insert($this->_table, $data);
        } catch(Exception $e) {
            throw new WrappedException('Error while inserting data', $e); }
        return $this->_db->lastInsertId();
    }

    protected function doUpdate($crits, $data, $opts)
    {
        try {
        return $this->_db->update($this->_table, $data, $crits);
        } catch(Exception $e) {
            throw new WrappedException('Error while updating data', $e); }
    }

    protected function doRemove($crits, $opts)
    {
        try {
        return $this->_db->remove($this->table, $crits);
        } catch(Exception $e) {
            throw new WrappedException('Error while removing data', $e); }
    }

    protected function doFindOne($crits)
    {
        try {
            $select = $this->_db->select()
                                ->from($this->_table)
                                ->where(implode(' AND ', array_keys($crits)));
            $ret = $this->_db->query($select, array_values($crits));
            if (!is_null($ret)) {
                $ret = $ret->fetchObject();
                if (false !== $ret) {
                    $out = array();
                    foreach ($ret as $k=>$v) {
                        $out[$k] = $v;
                    }
                    return $out;
                }
            }
            return false;
        } catch(Exception $e) {
            throw new WrappedException('Error while fetching data', $e); }
    }

    protected function doFind($crits, $sort, $limit, $offset)
    {
        try {
            $vals = array_values($crits);
            // $sort is expected to be array('col'=>-1|1...). Transform into array('col ASC|DESC',...)
            $sort = array_map(create_function('$v,$d', 'return $v." ".((-1==$d)?"DESC":"ASC");'),
                                              array_keys($sort), array_values($sort));
            $ret = $this->_db->query(
                     $this->_db->select()
                               ->from($this->_table)
                               ->where(implode(' AND ', array_keys($crits)))
                               ->order($sort)
                               ->limit($limit, $offset), $vals);

            return $ret->fetchAll(Zend_Db::FETCH_ASSOC);
        } catch(Exception $e) {
            throw new WrappedException('Error while fetching data', $e); }
    }

    protected function doCount($crits)
    {
        try {
            $stmt = $this->_db->query(
                      $this->_db->select()
                                ->from($this->_table)
                                ->where(array_keys($crits)));
            return count($stmt->execute(array_values($crits)));
        } catch(Exception $e) {
            throw new WrappedException('Error while counting data', $e); }
    }

    protected function doInit() {}
    protected function createFindInstance() { $c = get_class($this); return new $c(); }
    protected function doGetUniqueIds() { return array('_id'); }

    abstract protected function getTable();
}
