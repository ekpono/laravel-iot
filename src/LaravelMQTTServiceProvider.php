<?php

namespace Xeviant\LaravelIot;

use React\EventLoop\Factory;
use Xeviant\LaravelIot\Console\Commands\MqttListenerStart;
use Xeviant\LaravelIot\Console\Commands\MqttTopics;
use Xeviant\LaravelIot\Console\Commands\MQTTListenerRestart;
use Xeviant\LaravelIot\Foundation\MqttManager;
use Xeviant\LaravelIot\Foundation\MqttPublisher;
use Xeviant\LaravelIot\Foundation\MqttRouter;
use Xeviant\LaravelIot\Foundation\MQTTClient;
use Xeviant\LaravelIot\Foundation\MqttHandler;
use Xeviant\LaravelIot\Foundation\MQTTListener;
use Xeviant\LaravelIot\Helpers\Console;
use Xeviant\LaravelIot\Mqtt\Contracts\MQTTClientInterface;
use Xeviant\LaravelIot\Mqtt\Contracts\MQTTHandlerInterface;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use React\Dns\Resolver\Factory as DNSResolverFactory;
use React\EventLoop\LoopInterface;
use React\Socket\DnsConnector;
use React\Socket\TcpConnector;

class LaravelMQTTServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . "/../config/config.php" => config_path("mqtt.php")
            ], 'laravel-mqtt-config');

            $this->publishes([
                __DIR__ . "/../routes/mqtt.php" => base_path("routes/topics.php")
            ], 'laravel-mqtt-topics');

            $this->bootCommands();
        }

        if (file_exists(base_path("routes/topics.php"))) {
            require base_path("routes/topics.php");
        }
    }

    public function bootCommands()
    {
        $this->app->singleton('command.mqtt.server.start', function ($app) {
            return new MqttListenerStart;
        });

        $this->app->singleton('command.mqtt.server.restart', function ($app) {
            return new MQTTListenerRestart;
        });

        $this->app->singleton('command.mqtt.topics', function ($app) {
            return new MqttTopics;
        });

        $this->commands([
            MqttListenerStart::class,
            MQTTListenerRestart::class,
            MqttTopics::class,
        ]);
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->registerEventLoopBindings();
        $this->registerMQTTEventHandlers();
        $this->registerMQTTClient();
        $this->registerRouter();
        $this->registerMQTTServer();
        $this->loadConfiguration();
        $this->registerMQTTPublisher();
        $this->registerManager();
        $this->registerConsoleWriter();
    }

    public function registerRouter()
    {
        $this->app->singleton(MqttRouter::class, function (Application $container) {
            return new MqttRouter($container->get(MQTTClientInterface::class));
        });

        $this->app->singleton('mqtt.router', function (Application $container) {
            return $container->make(MqttRouter::class);
        });
    }

    public function loadConfiguration()
    {
        $this->mergeConfigFrom(__DIR__ . "/../config/config.php", "mqtt");
    }

    protected function registerMQTTClient()
    {
        $this->app->singleton(MQTTClientInterface::class, function (Application $application) {
            $loop = $application->make(LoopInterface::class);
            $dnsResolverFactory = new DNSResolverFactory();
            $connector = new DnsConnector(new TcpConnector($loop), $dnsResolverFactory->createCached('8.8.8.8', $loop));
            return new MQTTClient($connector, $loop);
        });
    }

    protected function registerMQTTServer()
    {
        $this->app->singleton('xeviant.mqtt.listener', MQTTListener::class);
    }

    protected function registerMQTTEventHandlers()
    {
        $this->app->singleton(MQTTHandlerInterface::class, MqttHandler::class);
    }

    protected function registerEventLoopBindings()
    {
        $this->app->singleton(LoopInterface::class, function (Application $app) {
            return Factory::create();
        });
    }

    protected function registerMQTTPublisher()
    {
        $this->app->singleton('mqtt.publisher', function(Application $app) {
            return $app->make(MqttPublisher::class);
        });
    }

    protected function registerManager()
    {
        $this->app->singleton('xeviant.mqtt', MqttManager::class);
        $this->app->singleton(MqttManager::class, MqttManager::class);
    }

    protected function registerConsoleWriter()
    {
        $this->app->singleton(Console::class, Console::class);
    }
}
