<?php
declare(strict_types=1);

namespace CakeMailInterceptor;

use Cake\Core\BasePlugin;

/**
 * CakeMailInterceptor Plugin
 *
 * CakePHP 5.x plugin to intercept and redirect outgoing emails
 * to a specified address. Useful for development and staging environments.
 */
class CakeMailInterceptorPlugin extends BasePlugin
{
    /**
     * Do bootstrapping or not
     *
     * @var bool
     */
    protected bool $bootstrapEnabled = false;

    /**
     * Enable middleware
     *
     * @var bool
     */
    protected bool $middlewareEnabled = false;

    /**
     * Console middleware
     *
     * @var bool
     */
    protected bool $consoleEnabled = false;

    /**
     * Load routes or not
     *
     * @var bool
     */
    protected bool $routesEnabled = false;

    /**
     * Enable services
     *
     * @var bool
     */
    protected bool $servicesEnabled = false;
}
