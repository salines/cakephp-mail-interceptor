<?php

declare(strict_types=1);

/**
 * Test bootstrap file for MailInterceptor plugin.
 */

require dirname(__DIR__) . '/vendor/autoload.php';

use Cake\Core\Configure;
use Cake\Log\Log;

// Configure logging for tests
if (!Log::getConfig('default')) {
    Log::setConfig('default', [
        'className' => 'Array',
        'levels' => ['info', 'error', 'warning', 'debug'],
    ]);
}

// Set debug mode
Configure::write('debug', true);
