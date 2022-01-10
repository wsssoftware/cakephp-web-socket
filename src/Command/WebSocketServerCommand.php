<?php
declare(strict_types=1);

namespace WebSocket\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use WebSocket\ConfigurationReader;
use WebSocket\Server\Server;

/**
 * WebSocketServer command.
 */
class WebSocketServerCommand extends Command
{
    /**
     * @inheritDoc
     */
    protected function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser->addOption('test', ['boolean' => true]);

        return parent::buildOptionParser($parser);
    }

    /**
     * Implement this method with your command's logic.
     *
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return null|void|int The exit code or null for success
     * @throws \ReflectionException
     */
    public function execute(Arguments $args, ConsoleIo $io)
    {
        $isTest = boolval($args->getOption('test'));
        $server = new Server($io, ConfigurationReader::getInstance());
        //test

        $server->run($isTest);
    }
}
