<?php

/**
 * This test requires a redis server on tcp://127.0.0.1:6379
 * All keys in $this->unitTestDatabase will be deleted
 */
class YiiPredisCacheTest extends CTestCase
{

    /**
     * @var YiiPredisCache
     */
    protected $object;
    /**
     * Redis database used to unit tests
     * All keys in this database will be deleted!
     * @var int
     */
    protected $unitTestDatabase = 5;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->object = new YiiPredisCache;
        $this->object->connectionParameters['database'] = $this->unitTestDatabase;
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
        $this->object->flushValues();
    }

    /**
     * @covers RedisCache::init
     */
    public function testInit()
    {
        $this->object->init();
        $this->assertTrue($this->object->isInitialized, "Failed to assert that object was initialized");
    }

    /**
     * @covers RedisCache::getClient
     */
    public function testGetClient()
    {
        $client = $this->object->getClient();
        $this->assertTrue($client instanceof \Predis\Client);
    }

    /**
     * @covers RedisCache::predisFactory
     */
    public function testPredisFactory()
    {
        $client = $this->object->predisFactory(array(), array());
        $this->assertTrue($client instanceof \Predis\Client);
    }

    /**
     * Test that setValue returns true on success
     * @covers RedisCache::setValue
     */
    public function testSetValue()
    {
        $key = 'testKey' . uniqid();
        $value = 'testValue' . uniqid();
                
        $success = $this->object->setValue($key, $value, 10);
        $this->assertTrue($success === true);
    }
    
    /**
     * Test cache hit using high level set/get methods
     * @covers RedisCache::get
     * @covers RedisCache::set
     */
    public function testCacheHit()
    {
        $key = 'testKey'.uniqid();
        $value = 'testValue' .uniqid();
        
        $success = $this->object->set($key, $value, 10);
        $this->assertTrue($success);
        
        $ret = $this->object->get($key);
        $this->assertEquals($value, $ret);
    }
    
    /**
     * Test cache hit using lower level setValue/getValue methods
     * @covers RedisCache::getValue
     * @covers RedisCache::setValue
     */
    public function testCacheHitLow()
    {
        $key = 'testKey'.uniqid();
        $value = 'testValue' .uniqid();
        
        $success = $this->object->setValue($key, $value, 10);
        $this->assertTrue($success === true);
        
        $ret = $this->object->getValue($key);
        $this->assertEquals($value, $ret);
    }
    
    /**
     * @covers RedisCache::getValue
     */
    public function testCacheMiss()
    {
        $key = 'testKey'.uniqid();
        $ret = $this->object->getValue($key);
        $this->assertTrue($ret === false);
    }

    /**
     * @covers RedisCache::addValue
     */
    public function testAddValueExistingKey()
    {
        $key = 'key'.uniqid();
        $value = 'value'.uniqid();
        $value2 = 'value2'.uniqid();
        
        // 1st add
        $success = $this->object->addValue($key, $value, 60);
        $this->assertTrue($success === true);
        // 2nd add
        $success = $this->object->addValue($key, $value2, 60);
        $this->assertTrue($success === false);
        
        $ret = $this->object->getValue($key);
        $this->assertEquals($value, $ret);
    }
    
    /**
     * @covers RedisCache::addValue
     */
    public function testAddValueNonExistingKey()
    {
        $key = 'key'.uniqid();
        $value = 'value'.uniqid();
        $success = $this->object->addValue($key, $value, 60);
        $this->assertTrue($success === true);
        
        $ret = $this->object->getValue($key);
        $this->assertEquals($value, $ret);
    }

    /**
     * @covers RedisCache::deleteValue
     */
    public function testDeleteValueExistingKey()
    {
        // set
        $key = 'key'.uniqid();
        $value = 'value'.uniqid();
        $success = $this->object->setValue($key, $value, 60);
        $this->assertTrue($success === true);
        
        // delete
        $ret = $this->object->deleteValue($key);
        $this->assertTrue($ret === true);
    }
    
    /**
     * @covers RedisCache::deleteValue
     */
    public function testDeleteValueNonExistingKey()
    {
        $key = 'key'.uniqid();
        
        // delete
        $success = $this->object->deleteValue($key);
        $this->assertTrue($success === true);
    }
    
    /**
     * @covers RedisCache::getValues
     */
    public function testGetValues()
    {
        $test = array(
            'key1'.  uniqid() => 'value1'.  uniqid(),
            'key2'.  uniqid() => 'value2'.  uniqid(),
            'key3'.  uniqid() => 'value3'.  uniqid(),
        );
        
        foreach ($test as $key => $value) {
            $this->object->setValue($key, $value, 10);
        }
        
        $ret = $this->object->getValues(array_keys($test));
        $this->assertEquals($test, $ret);
    }
    
    /**
     * @covers RedisCache::flushValues
     */
    public function testFlushValues()
    {
        $key = 'key'.uniqid();
        $value = 'value'.uniqid();
        
        // set
        $this->object->setValue($key, $value, 10);
        
        // get returns correct value
        $ret = $this->object->getValue($key);
        $this->assertEquals($value, $ret);;
        
        // flush
        $this->object->flushValues();
        
        // assert that get returns no data
        $ret = $this->object->getValue($key);
        $this->assertTrue($ret === false);
    }
    
    /**
     * @covers RedisCache::setValue
     * @covers RedisCache::getValue
     */
    public function testArrayAccess()
    {
        $key = 'key' .uniqid();
        $value = 'value' .uniqid();
                
        $this->object[$key] = $value;
        $this->assertEquals($value, $this->object[$key]);
    }

}
