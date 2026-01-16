<?php

declare(strict_types=1);

namespace MailInterceptor;

use Cake\Core\BasePlugin;

class MailInterceptorPlugin extends BasePlugin
{
    protected bool $bootstrapEnabled = false;
    protected bool $middlewareEnabled = false;
    protected bool $consoleEnabled = false;
    protected bool $routesEnabled = false;
    protected bool $servicesEnabled = false;
}
