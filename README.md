# EXS-silex-simplemongo-provider
A simple Silex provider to persist and execute queries on MongDB database on php7

## Installing the EXS-silex-simplemongo-provider in a Silex project
Open the composer.json file and add the EXS-silex-simplemongo-provider as a dependency:
``` js
//composer.json
//...
"require": {
        //other bundles
        "exs/silex-simplemongo-provider": "^1.0"
```
Save the file and have composer update the project via the command line:
``` shell
php composer.phar install
```

Or you could just add it via the command line:
```
$ composer.phar require exs/silex-simplemongo-provider
```

Update the app.php to include EXS-silex-simplemongo-provider:
``` php
//app.php
//...
$app->register(new \EXS\SimpleMongoProvider\Providers\Services\SimpleMongoProvider());
```
Update your mongodb connection and environment in your config.php:
```php
//...
$app['mongo.connections'] = array(
    'connection' => 'mongodb://localhost:27017',
    'dbname' => 'DB_NAME'
);
//...
```



## USAGE

Register the service in your service provider
``` php
    public function register(Container $container)
    {
        $container[YOUR_SERVICE_NAME] = ( function ($container) {
            return new YOUR_SERVICE(
                $container['exs.serv.mongo']
                );                
        });
    }
```

In your service
```php
public function __construct(\EXS\SimpleMongoProvider\Services\SimpleMongoService $mongo_service)
{
    $this->mongo_service = $mongo_service;
}
.
.
.

// Insert
$this->mongo_service->persist(YOUR_CLASS_OR_ARRAY);   
$result = $this->mongo_service->flush(COLLECTION_NAME); // the result will store the number of inserted entries or error message
if(!is_int($result) || $result == 0) {
    throwException($result);
}

// Update
$filter = ['product' => 6];
$this->mongo_service->update($filter, YOUR_CLASS_OR_ARRAY);   
$result = $this->mongo_service->flush(COLLECTION_NAME); 
 
// Get data with query
$filter = ['product' => 6];
$option = ['projection' => ['_id' => 0]];

$result = $$this->mongo_service->exeQuery($filter, $option, COLLECTION_NAME);
// $result will contain results in an array
```


#### Contributing ####
Anyone and everyone is welcome to contribute.

If you have any questions or suggestions please [let us know][1].

[1]: http://www.ex-situ.com/
