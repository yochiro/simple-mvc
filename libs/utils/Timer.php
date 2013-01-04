<?php
/**
 * @package smvc
 * @copyright Copyright (c) 2010, Yoann Mikami
 */

/**
 * Implements named timers with multiple intervals
 *
 * @package smvc
 */
class Utils_Timer
{

    /** @var array start times (name => [float, ...]) */
    protected $_starts;

    /** @var array stop times (name => [float, ...]) */
    protected $_stops;

    /** @var array interval times (name => [float, ...]) */
    protected $_intervals;

    /** @var array total times (name => float) */
    protected $_totals;

    /**
     * Initializes the member variables but does not start any timers
     */
    public function __construct()
    {
        $this->_starts = array();
        $this->_stops = array();
        $this->_intervals = array();
        $this->_totals = array();
    }

    /**
     * Resets a named timer
     *
     * @param string $name the name of the timer
     */
    public function reset($name='[DEFAULT]')
    {
        $this->_starts[$name] = array();
        $this->_stops[$name] = array();
        $this->_intervals[$name] = array();
        $this->_totals[$name] = 0.0;
    }

    /**
     * Starts a named timer
     *
     * @param string $name the name of the timer
     * @throws Exception if the timer is already running
     */
    public function start($name='[DEFAULT]')
    {
        if (!isset($this->_starts[$name])) {
            $this->reset($name);
        } else if (count($this->_starts[$name]) !=
                   count($this->_stops[$name])) {
            throw new Exception("timer $name already running");
        }
        list($usec, $sec) = explode(' ', microtime());
        $this->_starts[$name][] = (float) $sec + (float) $usec;
    }

    /**
     * Stops a named timer
     *
     * @param string $name the name of the timer
     * @throws Exception if the timer is not found
     * @throws Exception if the timer is not running
     */
    public function stop($name='[DEFAULT]')
    {
        list($usec, $sec) = explode(' ', microtime());
        if (!isset($this->_starts[$name])) {
            throw new Exception("timer $name not found");
        }
        $time_stop = (float) $sec + (float) $usec;
        $this->_stops[$name][] = $time_stop;
        $num_times = count($this->_starts[$name]);
        if (count($this->_stops[$name]) != $num_times) {
            throw new Exception("timer $name not running");
        }
        $diff = round(($time_stop - $this->_starts[$name][$num_times-1])
                      * 1000, 2);
        $this->_intervals[$name][] = $diff;
        $this->_totals[$name] += $diff;
    }

    /**
     * Gets the current offset time of a running named timer
     *
     * @param string $name the name of the timer
     * @return float time in milliseconds
     * @throws Exception if the timer is not found
     * @throws Exception if the timer is not running
     */
    public function getCurrentTime($name='[DEFAULT]')
    {
        list($usec, $sec) = explode(' ', microtime());
        if (!isset($this->_starts[$name])) {
            throw new Exception("timer $name not found");
        }
        $num_times = count($this->_starts[$name]);
        if (count($this->_stops[$name]) != $num_times-1) {
            throw new Exception("timer $name not running");
        }
        $time_cur = (float) $sec + (float) $usec;
        return round(($time_cur - $this->_starts[$name][$num_times-1])
                     * 1000, 2);
    }

    /**
     * Gets the current total time of a running named timer
     *
     * @param string $name the name of the timer
     * @return float time in milliseconds
     * @throws Exception if the timer is not found
     * @throws Exception if the timer is not running
     */
    public function getCurrentTotalTime($name='[DEFAULT]')
    {
        $time_tot = $this->getCurrentTime($name);
        return $time_tot + $this->_totals[$name];
    }

    /**
     * Gets the total time of a named timer
     *
     * @param string $name the name of the timer
     * @return float time in milliseconds
     * @throws Exception if the timer is not found
     * @throws Exception if the timer is running
     */
    public function getTotalTime($name='[DEFAULT]')
    {
        if (!isset($this->_starts[$name])) {
            throw new Exception("timer $name not found");
        } else if (count($this->_starts[$name]) !=
                   count($this->_stops[$name])) {
            throw new Exception("timer $name is running");
        }
        return $this->_totals[$name];
    }

    /**
     * Gets the total time of a named timer, running or not
     *
     * @param string $name the name of the timer
     * @return float time in milliseconds
     * @throws Exception if the timer is not found
     */
    public function getTime($name='[DEFAULT]')
    {
        list($usec, $sec) = explode(' ', microtime());
        if (!isset($this->_starts[$name])) {
            throw new Exception("timer $name not found");
        }
        $time_tot = $this->_totals[$name];
        $num_times = count($this->_starts[$name]);
        if (count($this->_stops[$name]) == $num_times-1) {
            $time_cur = (float) $sec + (float) $usec;
            $time_tot += round(($time_cur - $this->_starts[$name][$num_times-1])
                               * 1000, 2);
        }
        return $time_tot;
    }

    /**
     * Returns a string representation of the getTime() of the last named timer
     *
     * @return string getTime() of the last named timer
     */
    public function __toString()
    {
        $str = '0.0';
        $names = array_keys($this->_starts);
        $num_timers = count($names);
        if (0 < $num_timers) {
            $str = strval($this->getTime($names[$num_timers-1]));
        }
        return $str;
    }

    /**
     * Gets the number of intervals within a named timer
     *
     * @param string $name the name of the timer
     * @param int number of intervals
     * @throws Exception if the timer is not found
     * @throws Exception if the timer is running
     */
    public function getCount($name='[DEFAULT]')
    {
        if (!isset($this->_starts[$name])) {
            throw new Exception("timer $name not found");
        }
        $num_times = count($this->_starts[$name]);
        if (count($this->_stops[$name]) != $num_times) {
            throw new Exception("timer $name is running");
        }
        return $num_times;
    }

    /**
     * Gets the average interval time for a named timer
     *
     * @param string $name the name of the timer
     * @return float time in milliseconds
     * @throws Exception if the timer is not found
     * @throws Exception if the timer is running
     */
    public function getAverageTime($name='[DEFAULT]')
    {
        $num_times = $this->getCount($name);
        return $this->_totals[$name] / $num_times;
    }

    /**
     * Gets interval statistics for a named timer
     *
     * @param string $name the name of the timer
     * @return array of (interval_num, start_time, stop_time, duration)
     * @throws Exception if the timer is not found
     * @throws Exception if the timer is running
     */
    public function getStats($name='[DEFAULT]')
    {
        if (!isset($this->_starts[$name])) {
            throw new Exception("timer $name not found");
        }
        $num_times = count($this->_starts[$name]);
        if (count($this->_stops[$name]) != $num_times) {
            throw new Exception("timer $name is running");
        }
        $stats = array();
        for ($i=0; $i<$num_times; ++$i) {
            $stats[] = array($i+1,
                             $this->_starts[$name][$i],
                             $this->_stops[$name][$i],
                             $this->_intervals[$name][$i]);
        }
        return $stats;
    }

    /**
     * Gets the total time of all named timers
     *
     * @return float time in milliseconds
     * @throws Exception if a timer is running
     */
    public function getAggregateTotalTime()
    {
        $time_aggtot = 0.0;
        foreach ($this->_totals as $name => $time_tot) {
            if (count($this->_starts[$name]) !=
                count($this->_stops[$name])) {
                throw new Exception("timer $name is running");
            }
            $time_aggtot += $time_tot;
        }
        return $time_aggtot;
    }

    /**
     * Gets the number of named timers
     *
     * @return int number of named timers
     */
    public function getAggregateCount()
    {
        return count($this->_totals);
    }

    /**
     * Gets the average time among all named timers
     *
     * @return float time in milliseconds
     */
    public function getAggregateAverageTime()
    {
        return $this->getAggregateTotalTime() / count($this->_totals);
    }

    /**
     * Gets statistics for named timers
     *
     * @return array of (name, duration, num_intervals)
     */
    public function getAggregateStats()
    {
        $stats = array();
        foreach (array_keys($this->_totals) as $name) {
            $stats[] = array($name,
                             $this->_totals[$name],
                             count($this->_intervals[$name]));
        }
        return $stats;
    }

}
