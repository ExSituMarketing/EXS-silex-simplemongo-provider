<?php

namespace EXS\SimpleMongoProvider\Services;

use \MongoDB\Driver\Manager;
use \MongoDB\Driver\BulkWrite;
use \MongoDB\Driver\Query;
use \MongoDB\Driver\WriteConcern;
use EXS\SimpleMongoProvider\Services\CreateCollection;

class SimpleMongoService
{        
    /**
     * MongoDB connection manager
     *
     * @var \MongoDB\Driver\Manager
     */
    protected $manager;
    
    /**
     * Array queue to store all MongoDb actions
     *
     * @var \MongoDB\Driver\BulkWrite
     */
    protected $bulk;
    
    /**
     * MongoDb database name to be connected
     *
     * @var string
     */
    protected $dbname;

    /**
     * Initiate the service
     * 
     * @param arary $connection
     */
    public function __construct($connection)
    {        
        // get connection manager
        if(isset($connection['connection'])) {
            $this->manager = $this->getManager($connection['connection']);
        }
        
        // set dbname
        if(isset($connection['dbname'])) {
            $this->dbname = $connection['dbname'];
        }
        
        // set the queue for bulk actions 
        $this->bulk = $this->setBulkWriteStorage();
    }   
    
    /**
     * Get MongoDb manager
     * 
     * @return \MongoDB\Driver\Manager
     */
    public function getManager($connection)
    {
        try {
            $manager = new Manager($connection);
        } catch (\Exception $e) {
            throwException($e->getMessage());
        }
        return $manager;
    }    
    
    /**
     * Queue insert action
     * 
     * @param mixed $data
     * @return boolean
     */
    public function persist($data)
    {
        $mappedData = $this->validateData($data);        
        if(!empty($mappedData)) {
            try {
                $this->bulk->insert($mappedData);
                return true;
            } catch (\MongoDB\Driver\Exception\Exception $e) {
                return $e->getMessage();
            }
        }
        return false;
    }
    
    /**
     * Update collection
     * 
     * @param array $filter
     * @param mioxed $data
     * @return boolean
     */
    public function update($filter, $data)
    {
        $mappedData = $this->validateData($data);        
        if(!empty($mappedData)) {
            try {
                $this->bulk->update($filter, $mappedData);
                return true;
            } catch (\MongoDB\Driver\Exception\Exception $e) {
                return $e->getMessage();
            }
        }
        return false;
    }  
    
    /**
     * Validate data for mapping
     * 
     * @param mixed $data
     * @return array
     */
    public function validateData($data)
    {
        if (is_array($data)) {
            return $data;
        } else if (is_object($data)) {
            return  $this->createDocumentFromObject($data);
        }
        return [ $data ];               
    }
    
    /**
     * Execute the bulk queue
     * 
     * @param string $collection
     * @return mixed
     */
    public function flush($collection)
    {
        $db = $this->dbname . '.' . $collection;
        $writeConcern = new WriteConcern(WriteConcern::MAJORITY, 100);
        try {        
            $result = $this->manager->executeBulkWrite($db, $this->bulk, $writeConcern);
            $result = $result->getInsertedCount();
        } catch (\MongoDB\Driver\Exception\BulkWriteException $e) {
            $result = $e->getWriteResult();
        } catch (\MongoDB\Driver\Exception\Exception $e) {
            $result = $e->getMessage();
        }
        $this->bulk = $this->setBulkWriteStorage(); // initiate the queue
        return $result;        
    }    
    
    /**
     * Map the object to MongoDB executable array
     * 
     * @param object $object
     * @return array
     */
    public function createDocumentFromObject($object)
    {
        $result = array();
        $methods = get_class_methods($object);
        $result = $this->convertToArray($result, $methods, $object);
        return $result;        
    }
    
    /**
     * Convert the object to array using its getters.
     * 
     * @param array $result
     * @param array $methods
     * @param object $object
     * @return array
     */
    public function convertToArray($result, $methods, $object)
    {
        foreach ($methods as $method) {
            $result = $this->processGetters($result, $method, $object);
        }      
        return $result;
    }
    
    /**
     * Process getter method to get the value of the property
     * 
     * @param array $result
     * @param string $method
     * @param object $object
     * @return array
     */
    public function processGetters($result, $method, $object)
    {
        if (substr($method, 0, 3) == 'get') {
            $propName = strtolower(substr($method, 3, 1)) . substr($method, 4);
            if(strtolower($propName) == 'id') {
                return $result;
            }
            $value = $object->$method();
            $result[$propName] = $this->getPropertyValue($value);
        }        
        return $result;
    }
    
    /**
     * Get the value of the property
     * 
     * @param mixed $value
     * @return mixed
     */
    public function getPropertyValue($value)
    {
        if(is_object($value)) {
            if($value instanceOf \DateTime) {
                return get_object_vars($value);
            } 
            return $this->createDocumentFromObject($value);                               
        }            
        return $value;        
    }
    
    /**
     * Execute the query to get documents 
     * 
     * @param array $filter
     * @param array $options
     * @param string $collection
     * @return mixed
     */
    public function exeQuery($filter, $options, $collection)
    {
        $db = $this->dbname . '.' . $collection;
        try {
            $query = new Query($filter, $options);
            return $this->manager->executeQuery($db, $query)->toArray();                        
        } catch (\MongoDB\Driver\Exception $ex) {
            return $ex->getMessage();
        }
    }
    
    /**
     * Initiate bulk write storage
     * 
     * @param boolean $ordered
     * @return \MongoDB\Driver\BulkWrite
     */
    public function setBulkWriteStorage($ordered = false)
    {
        if($ordered === true) {
            return new BulkWrite(['ordered' => true]);
        }
        return new BulkWrite(['ordered' => false]);
    }     
    
    /**
     * Create the new collection
     * 
     * @param string $collectionName
     * @param obj $options
     * @return string
     */
    public function createNewCollection($collectionName, $options)
    {
        $createCollection = new CreateCollection($collectionName);
        $this->setIndexOption($createCollection, $options);
        $this->setCappedOption($createCollection, $options);

        try {
            $command = $createCollection->getCommand();
            $this->manager->executeCommand($this->dbname, $command);
            return $collectionName. ' created';
        } catch(\MongoDB\Driver\Exception $e) {
            return $e->getMessage();
        }        
    }
    
    /**
     * Set auto index option for the new collection
     * 
     * @param CreateCollection $createCollection
     * @param obj $options
     */
    public function setIndexOption(CreateCollection $createCollection, $options = null) 
    {
        if(isset($options->index) && $options->index == 'false') {
            $createCollection->setAutoIndexId(false);
        }        
    }
    
    /**
     * Set capped option for the new collection
     * 
     * @param CreateCollection $createCollection
     * @param obj $options
     */
    public function setCappedOption(CreateCollection $createCollection, $options = null) 
    {        
        if(isset($options->cap) && $options->cap == 'true') {
            $createCollection->setCappedCollection($options->maxbyte, $options->maxdocs);
        }        
    }   
}








// end of script
