#!/usr/bin/php -q
<?php
declare(strict_types=1);

require dirname(__DIR__, 3) . '/vendor/autoload.php';

use Cake\Console\CommandRunner;
use TestApp\Application;

// Build the runner with an application and root executable name.
$runner = new CommandRunner(new Application(dirname(__DIR__) . '/config'), 'cake');
exit($runner->run($argv));
