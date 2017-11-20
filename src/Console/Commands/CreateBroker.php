<?php

namespace losted\SSO\Console\Commands;

use losted\SSO\Models\Broker;

class CreateBroker extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'sso:create-broker {broker_id?}';

    /**
     * The console command description.
     */
    protected $description = 'Create a Broker for the SSO';

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

        $broker_id     = !empty($this->argument('broker_id')) ? $this->argument('broker_id') : 'broker_id_' . uniqid();
        $broker_secret = 'broker_secret_' . uniqid();

        $broker = Broker::create([
            'broker_id'     => $broker_id,
            'broker_secret' => $broker_secret
        ]);

        if($broker) {
            $this->info("SSO Broker created successfuly!");
            $this->info("Broker ID: $broker_id");
            $this->info("Broker Secret: $broker_secret");
        } else {
            $this->error('Something went wrong!');
        }

    }
}
