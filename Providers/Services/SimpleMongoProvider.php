<?php

namespace EXS\SimpleMongoProvider\Providers\Services;

use Pimple\ServiceProviderInterface;
use Pimple\Container;
use EXS\SimpleMongoProvider\Services\SimpleMongoService;


/**
 * Description of simple mongo serivce provider
 * 
 * Register the service
 * Created      10/07/2016
 * @author      Lee
 * @copyright   Copyright 2016 ExSitu Marketing.
 */
class SimpleMongoProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        $app['exs.serv.mongo'] = (function ($app) {
            return new SimpleMongoService($app['mongo.connections']);
        });
    }
}
