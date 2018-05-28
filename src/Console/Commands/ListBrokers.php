<?php

namespace Losted\SSO\Console\Commands;

use Losted\SSO\Models\Broker;

class ListBrokers extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'sso:list-brokers';

    /**
     * The console command description.
     */
    protected $description = 'List all the SSO Brokers';

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->check_sso_table();

        $brokers = Broker::all();

        $this->info('-----------------------------------------------------');
        $this->info('All Brokers');
        $this->info('-----------------------------------------------------');

        if ($brokers->count()) {
            foreach ($brokers as $broker) {
                $this->info("Broker ID: {$broker->broker_id}");
                $this->info("Broker Secret: {$broker->broker_secret}");
                $this->info('-----------------------------------------------------');
            }
        } else {
            $this->info('No broker found.');
            $this->info('-----------------------------------------------------');
        }
    }
}
