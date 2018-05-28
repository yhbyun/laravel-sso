<?php

namespace Losted\SSO\Console\Commands;

use Illuminate\Support\Facades\Schema;

class Command extends \Illuminate\Console\Command
{
    protected function check_sso_table()
    {
        if (!Schema::hasTable(config('sso.brokers_table'))) {
            $this->error(sprintf('The %s table is not found.', config('sso.brokers_table')));
            $this->error('This command is meant to be run on the server side of the SSO.');
            $this->error("If you think you're on the right side, maybe you forgot to migrate the table.");
            exit;
        }
    }
}
