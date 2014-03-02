<?php

/**
 * Implementation of CCache using Predis php client to comminicate with Redis.
 * @see https://github.com/nrk/predis
 */
class YiiPredisCache extends CCache
{

    /**
     * Connection parameters for one or multiple servers.
     * @var array
     * @see https://github.com/nrk/predis/wiki/Connection-Parameters
     */
    protected $defaultConnectionParameters = array(
        /**
         * Specifies the protocol used to communicate with an instance of Redis.
         * By default Predis supports 'tcp' (TCP/IP), 'unix' (UNIX domain sockets) or 'http' (HTTP protocol through Webdis).
         * @var string
         */
        'scheme' => 'tcp',
        /**
         * IP or hostname of the target server. This is ignored when connecting to Redis using UNIX domain sockets.
         * @var string
         */
        'host' => '127.0.0.1',
        /**
         * TCP/IP port of the target server. This is ignored when connecting to Redis using UNIX domain sockets.
         * @var string
         */
        'port' => 6379,
        /**
         * Accepts a numeric value that is used by Predis to automatically select a logical database with the SELECT command.
         * @var int
         */
        'database' => 0,
        /**
         * Specifies if the underlying connection resource should be left open when a script ends its lifecycle.
         * @var bool
         */
        'connection_persistent' => true,
    );

    /**
     * Connection settings
     * @var array
     */
    public $connectionParameters = array();

    /**
     * Options that specify certain behaviours for the client.
     * @var array
     * @see https://github.com/nrk/predis/wiki/Client-Options
     */
    protected $defaultOptions = array(
        /**
         * The value must match the version of Redis that is being used and specifies which commands or features are available to the client.
         * @var string
         */
        'profile' => '2.6',
    );

    /**
     * Client options
     * @var array
     */
    public $options = array();

    /**
     * @var null|array|false
     * @see http://www.yiiframework.com/doc/api/1.1/CCache#serializer-detail
     */
    public $serializer = null;
    
    /**
     * Redis client
     * @var \Predis\Client
     */
    protected $client;

    /**
     * Initializes the application component.
     * @return null
     */
    public function init()
    {
        // merge default and custom settings to get final settings
        $this->connectionParameters = array_merge($this->defaultConnectionParameters, $this->connectionParameters);
        $this->options = array_merge($this->defaultOptions, $this->options);

        return parent::init();
    }

    /**
     * @return \Predis\Client
     */
    public function getClient()
    {
        if ($this->client === null) {
            $this->client = $this->predisFactory($this->connectionParameters, $this->options);
        }

        return $this->client;
    }

    /**
     * Factory method to create Predis client
     * @return \Predis\Client
     */
    public function predisFactory($parameters, $options)
    {
        return new \Predis\Client($parameters, $options);
    }

    /**
     * Retrieves a value from cache with a specified key.
     * @param string $key A unique key identifying the cached value.
     * @return string|bool The value stored in cache, false if the value is not in the cache or expired.
     */
    public function getValue($key)
    {
        /**
         * $ret === null on failure
         * $ret is value on success
         */
        $ret = $this->getClient()->get($key);
        return ($ret === null) ? false : $ret;
    }

    /**
     * Stores a value identified by a key in cache.
     * @param string $key The key identifying the value to be cached.
     * @param string $value The value to be cached.
     * @param int $expire The number of seconds in which the cached value will expire, 0 means never expire.
     * @return bool True if the value is successfully stored into cache, false otherwise.
     */
    public function setValue($key, $value, $expire)
    {
        if ($expire === 0) {
            
            $ret = $this->getClient()->set($key, $value);
        } else {
            
            $ret = $this->getClient()->set($key, $value, 'ex', (int) $expire);
        }

        return ($ret === true);
    }

    /**
     * Stores a value identified by a key into cache if the cache does not contain this key.
     * @param string $key The key identifying the value to be cached.
     * @param string $value The value to be cached.
     * @param integer $expire The number of seconds in which the cached value will expire. 0 means never expire.
     * @return bool If the value is successfully stored into cache, false otherwise.
     */
    public function addValue($key, $value, $expire)
    {
        if ($expire === 0) {

            $ret = $this->getClient()->set($key, $value, 'nx');
        } else {

            $ret = $ret = $this->getClient()->set($key, $value, 'ex', (int) $expire, 'nx');
        }

        /**
         * $ret === null on failure
         * $ret === true on success
         */
        return ($ret === true);
    }

    /**
     * Deletes a value with the specified key from cache.
     * @param string $key The key of the value to be deleted.
     * @return bool If no error happens during deletion.
     */
    public function deleteValue($key)
    {
        /**
         * Number of keys that were removed
         * 0 - key does not exist
         * 1 - one key was removed
         * @var int
         */
        $ret = $this->getClient()->del($key);
        return ($ret === 0 || $ret === 1);
    }
    
    /**
     * Retrieves multiple values from cache with the specified keys.
     * @param array $keys A list of keys identifying the cached values.
     * @return array A list of cached values indexed by the keys
     */
    public function getValues($keys)
    {
        /**
         * Array of values, if key does not exist, value is null
         * @var array
         */
        $values = $this->getClient()->mget($keys);
        return array_combine($keys, $values);
    }
    
    /**
     * Deletes all values from cache.
     * Redis implementation performs flushdb on database specified in connection configuration.
     * @return bool whether the flush operation was successful.
     */
    public function flushValues()
    {
        $client = $this->getClient();
        // make sure correct database is selected
        $client->select($this->connectionParameters['database']);
        $ret = $this->getClient()->flushdb();
        return ($ret === true);
    }

}
