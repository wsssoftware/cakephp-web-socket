<?php
declare(strict_types=1);

namespace WebSocket\Test\TestCase\Command;

use Cake\Command\Command;
use Cake\Routing\Router;
use Cake\TestSuite\ConsoleIntegrationTestTrait;
use Cake\TestSuite\TestCase;
use RuntimeException;

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
    public function testExecuteWithTestOption(): void
    {
        $this->exec('web_socket_server --test');
        $this->assertOutputContains('command with test option finished');
        $this->assertExitCode(Command::CODE_SUCCESS);
    }

    /**
     * Test execute method
     *
     * @return void
     * @uses \WebSocket\Command\WebSocketServerCommand::execute()
     */
    public function testExecuteTwoInstances(): void
    {
        $this->expectException(RuntimeException::class);
        $this->exec('web_socket_server');
    }
}
