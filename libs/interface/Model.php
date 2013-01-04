<?php
/**
 * @package smvc
 * @copyright Copyright (c) 2010, Yoann Mikami
 */

/**
 * Defines an interface for a simple MVC model
 *
 * Basic CRUD operations must be supplied by the implementing class.
 * It also provides with additional feature to set find criteria beforehand,
 * then using the same instance to perform a search using previously set values
 * as its parameters.
 *
 * Create is performed by instantiating a new object then calling insert passing
 * data to insert as a parameter, rather than using the static create method.
 * (static methods are not very good OO concepts).
 *
 * The way model properties are accessed/set is up to the implementation.
 * The mapping between this model object and the underlying storage engine
 * is also up to the implementor.
 *
 * @package smvc
 */
interface Iface_Model
{
    /**
     * Inserts a new record using specified data
     *
     * Current instance properties will be set to the values found in data.
     * $options is an optional parameter which allows to pass implementation
     * specific options when needed.
     * The current updated object should be returned to allow method chaining.
     *
     * @param array $data record data to insert
     * @param mixed $options impl. specific options
     * @return $this for chaining
     */
    function insert($data, $options=null);

    /**
     * Updates current record using specified data
     *
     * Current instance properties will be set to the values found in data.
     * $options is an optional parameter which allows to pass implementation
     * specific options when needed.
     * The updated object should be returned to allow method chaining.
     *
     * @param array $data record data to update
     * @param mixed $options impl. specific options
     * @return $this for chaining
     */
    function update($data, $options=null);

    /**
     * Deletes record(s) matching specified criteria
     *
     * Current instance will also be deleted if it matches criteria.
     * $options is an optional parameter which allows to pass implementation
     * specific options when needed.
     * If record mapped by current instance is deleted, the object properties
     * are still available when the method returns; HOWEVER, update and find
     * should fail at this point, while create should work.
     *
     * @param array $crits criteria to match to delete record(s)
     * @param mixed $options impl. specific options
     * @return $this for chaining
     */

    function remove($crits=array(), $options=null);

    /**
     * Returns 0 or 1 record matching specified criteria
     *
     * If multiple records match $data, the first one should be returned.
     * Should no record match criteria, false should be returned.
     *
     * @param array $data find criteria
     * @return false|Iface_Model found record, or false if none found
     */
    function findOne($data=array());

    /**
     * Returns all records matching specified criteria
     *
     * Returned data can be sorted according to the optional sort parameters,
     * and/or limited to a subset using a limit or an offset.
     * Default if 0 for limit (no limit) and offset (returns from first record)
     * $sort is an array with field to sort as key, and 1|-1 as value;
     * 1 being ascending order, -1 being descending order.
     *
     * @param array $data set of criteria to match records against
     * @param array $sort array of fields to sort. format is key=>1|-1
     * @param integer $limit Limits number of records to specified limit. 0 if no limit
     * @param integer $offset Returns records starting at supplied offset. 0 if no offset
     * @return array[Iface_Model] list of matched records. Empty array if none
     */
    function find($data=array(), $sort=array(), $limit=0, $offset=0);

    /**
     * Counts number of records matching specified criteria
     *
     * Number returned ignores limit/offset values, so actual count
     * returned by count may differ from a count() on the array returned
     * by find should the latter have limit/offset different than 0.
     *
     * @param array $data set of criteria to match records against
     * @return integer number of matched records
     */
    function count($data=array());

    /**
     * Sets the find criteria to use when calling findSet
     *
     * This method is used jointly with findSet, and allows search criteria
     * and actual search call to be performed separately.
     * This adds stateful data on the instance. How it's done is up to the implementor.
     *
     * @param array $crits set of criteria to match records against
     * @return $this for chaining
     */
    function setFindCriteria($crits);

    /**
     * Sets the sorting parameters to use when calling findSet
     *
     * This method is used jointly with findSet, and allows sort parameters
     * and actual search call to be performed separately.
     * Format is field => 1|-1 (1=ascending, -1=descending)
     *
     * @param array $sort set of fields to sort
     * @return $this for chaining
     */
    function setFindSort($sort);

    /**
     * Finds records matching previously set criteria
     *
     * Records are also sorted according to previously set sort params.
     * Returned data can be sorted according to the optional sort parameters,
     * and/or limited to a subset using a limit or an offset.
     * Default if 0 for limit (no limit) and offset (returns from first record)
     *
     * @param integer $limit Limits number of records to specified limit. 0 if no limit
     * @param integer $offset Returns records starting at supplied offset. 0 if no offset
     * @return array[Iface_Model] list of matched records. Empty array if none
     */
    function findSet($limit=0, $offset=0);


    /**
     * Counts number of records matching previously set criteria
     *
     * Number returned ignores limit/offset values, so actual count
     * returned by count may differ from a count() on the array returned
     * by find should the latter have limit/offset different than 0.
     *
     * @return integer number of matched records
     */
    function countSet();
}
