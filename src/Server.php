<?php

namespace Losted\SSO;

use Losted\SSO\Contracts\Server as ServerContract;

class Server implements ServerContract
{
    use CommandHandler;
}
