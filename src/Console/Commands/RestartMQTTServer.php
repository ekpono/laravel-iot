<?php

namespace Bosunski\LaravelIot\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class RestartMQTTServer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mqtt:restart';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sends a Restart Signal to the MQTT Server!';

    protected $deviceIds = [];

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->laravel['cache']->forever('xeviant:mqtt:restart', Carbon::now()->getTimestamp());

        $this->info('MQTT Server Restart Signal Sent');
    }
}
