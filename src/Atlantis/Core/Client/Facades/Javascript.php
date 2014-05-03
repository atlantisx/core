<?php namespace Atlantis\Core\Client\Facades;

use Illuminate\Support\Facades\Facade;


class Javascript extends Facade {

    /**
     * Name of the binding in the IoC container
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'atlantis.client.javascript';
    }

} 