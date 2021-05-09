<?php

namespace Flasher\Notyf\Laravel\ServiceProvider\Providers;

use Flasher\Laravel\ServiceProvider\ResourceManagerHelper;
use Flasher\Notyf\Laravel\FlasherNotyfServiceProvider;
use Flasher\Notyf\Prime\NotyfFactory;
use Flasher\Prime\Flasher;
use Flasher\Prime\Response\Resource\ResourceManager;
use Illuminate\Container\Container;
use Illuminate\Foundation\Application;

class Laravel implements ServiceProviderInterface
{
    /**
     * @var Container
     */
    protected $app;

    public function __construct(Container $app)
    {
        $this->app = $app;
    }

    public function shouldBeUsed()
    {
        return $this->app instanceof Application;
    }

    public function boot(FlasherNotyfServiceProvider $provider)
    {
        $provider->publishes(array(
            flasher_path(__DIR__ . '/../../Resources/config/config.php') => config_path('flasher_notyf.php'),
        ), 'flasher-config');
    }

    public function register(FlasherNotyfServiceProvider $provider)
    {
        $provider->mergeConfigFrom(flasher_path(__DIR__ . '/../../Resources/config/config.php'), 'flasher_notyf');
        $this->appendToFlasherConfig();

        $this->registerServices();
    }

    public function registerServices()
    {
        $this->app->singleton('flasher.notyf', function (Container $app) {
            return new NotyfFactory($app['flasher.storage_manager']);
        });

        $this->app->alias('flasher.notyf', 'Flasher\Notyf\Prime\NotyfFactory');

        $this->app->extend('flasher', function (Flasher $manager, Container $app) {
            $manager->addFactory('notyf', $app['flasher.notyf']);

            return $manager;
        });

        $this->app->extend('flasher.resource_manager', function (ResourceManager $resourceManager) {
            ResourceManagerHelper::process($resourceManager, 'notyf');

            return $resourceManager;
        });
    }

    protected function appendToFlasherConfig()
    {
        $flasherConfig = $this->app['config']->get('flasher.adapters.notyf', array());

        $notyfConfig = $this->app['config']->get('flasher_notyf', array());

        $this->app['config']->set('flasher.adapters.notyf', array_merge($notyfConfig, $flasherConfig));
    }
}
