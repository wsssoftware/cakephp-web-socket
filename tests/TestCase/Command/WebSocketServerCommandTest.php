<?php
declare(strict_types=1);

namespace WebSocket\Test\TestCase\Command;

use Cake\TestSuite\ConsoleIntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * WebSocket\Command\WebSocketServerCommand Test Case
 *
 * @uses \WebSocket\Command\WebSocketServerCommand
 */
class WebSocketServerCommandTest extends TestCase
{
    use ConsoleIntegrationTestTrait;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->useCommandRunner();
    }

    /**
     * Test execute method
     *
     * @return void
     * @uses \WebSocket\Command\WebSocketServerCommand::execute()
     */
    public function testExecute(): void
    {
//        $this->exec('web_socket_server');
//
//        $output = $this->_out->output();

        $this->markTestIncomplete('Not implemented yet.');
    }
}
