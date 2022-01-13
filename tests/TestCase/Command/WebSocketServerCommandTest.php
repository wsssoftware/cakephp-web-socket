<?php
declare(strict_types=1);

namespace WebSocket\Test\TestCase\Command;

use Cake\Routing\Router;
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
        Router::reload();
    }

    /**
     * Test execute method
     *
     * @return void
     * @uses \WebSocket\Command\WebSocketServerCommand::execute()
     */
    public function testExecute(): void
    {
//        $this->exec('web_socket_server --test');
//        $output = $this->_out->output();
//        $this->assertExitCode(Command::CODE_SUCCESS);
//        $this->assertStringContainsString('[DEBUG] command unit test finished', $output);

        self::markTestSkipped();
    }
}
