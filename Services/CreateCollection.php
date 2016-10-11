<?php

namespace EXS\SimpleMongoProvider\Services;

use \MongoDB\Driver\Command;

class CreateCollection
{
    /**
     * CreateCollection array
     *
     * @var array
     */
    protected $cmd = array();

    /**
     * Initiate the service
     * 
     * @param string $collectionName
     */
    function __construct($collectionName) 
    {
        $this->cmd["create"] = (string)$collectionName;
    }
    
    /**
     * Set the new collection auto index function
     * 
     * @param string $bool
     */
    public function setAutoIndexId($bool) 
    {
        $this->cmd["autoIndexId"] = (bool)$bool;
    }
    
    /**
     * Set the new collection capped options
     * 
     * @param string $maxBytes
     * @param string $maxDocuments
     */
    public function setCappedCollection($maxBytes, $maxDocuments = false) 
    {
        $this->cmd["capped"] = true;        
        $this->cmd["size"]   = (int)$maxBytes;

        if ($maxDocuments) {
            $this->cmd["max"] = (int)$maxDocuments;
        }
    }
    
    /**
     * Get commands
     * 
     * @return Command
     */
    public function getCommand() 
    {
        return new Command($this->cmd);
    }
    
    /**
     * Get the new collection name
     * 
     * @return string
     */
    public function getCollectionName() 
    {
        return $this->cmd["create"];
    }
}
