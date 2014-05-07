<?php namespace Atlantis\Core;

use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\ServiceProvider;
use Atlantis\Core\Client\Javascript;
use Atlantis\Core\View;
use Atlantis\Core\Config;
use Atlantis\Core\Module;


class CoreServiceProvider extends ServiceProvider {

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;


	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
        $this->registerServiceModule();
        $this->registerServiceClient();
        $this->registerCommands();
	}


    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->package('atlantis/core');

        $this->startLoadAliases();
        $this->startLoadModules();
        $this->startLoadSupport();

        #e: Event trigger
        $this->app['events']->fire('atlantis.core.ready');
    }


    /**
     *
     *
     * @return void
     */
    public function registerServiceModule(){
        #i: Registering Module environment for facade
        $this->app['atlantis.module'] = $this->app->share(function($app){
            return new Module\Environment($app);
        });
    }


    /**
     *
     *
     * @return void
     */
    public function registerServiceClient(){
        $this->app->bind('atlantis.client.javascript',function($app){
            #i: Get configs
            $view = $app['config']->get('core::client.javascript.bind');
            $namespace = $app['config']->get('core::client.javascript.namespace');

            #i: Get view binder
            $binder = new View\Binder($app['events'],$view);

            #i: Return provider instance
            return new Javascript\Provider($binder,$namespace);
        });
    }


    /**
     *
     *
     * @return void
     */
    public function startLoadModules(){
        $this->app['atlantis.module']->register();
    }


    /**
     *
     *
     * @return void
     */
    public function startLoadAliases(){
        #i: Automatic Alias loader
        AliasLoader::getInstance()->alias(
            'Javascript',
            'Atlantis\Core\Client\Facades\Javascript'
        );
    }


    /**
     *
     *
     * @return void
     */
    public function startLoadSupport(){
        #i: Load events listener
        include __DIR__.'/../../events.php';
    }


    /**
     *
     *
     * @return void
     */
    public function registerCommands(){
        $this->app['atlantis.commands.module-make'] = $this->app->share(function($app){
            return new Module\Commands\ModuleMakeCommand($app['atlantis.module'],$app['files']);
        });

        $this->commands('atlantis.commands.module-make');
    }


	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array('atlantis.module','atlantis.client.javascript');
	}

}
