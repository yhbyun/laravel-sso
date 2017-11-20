<?php

namespace losted\SSO\Console\Commands;

use losted\SSO\Models\Broker;

class RemoveBroker extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'sso:remove-broker {broker_id}';

    /**
     * The console command description.
     */
    protected $description = 'Remove a Broker for the SSO';

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

        $broker = Broker::where('broker_id', $this->argument('broker_id'))->first();

        if($broker->delete()) {
            $this->info("SSO Broker successfuly deleted!");
        } else {
            $this->error('Something went wrong!');
        }

    }
}
