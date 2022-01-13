<?php
declare(strict_types=1);

namespace WebSocket\Test\TestCase;

use Cake\Core\Configure;
use Cake\TestSuite\TestCase;
use TestApp\Application;
use TestApp\Timers\TestTimer;
use WebSocket\ConfigurationReader;
use WebSocket\Server\Timer;

class ConfigurationReaderTest extends TestCase
{
    /**
     * @return void
     */
    public function testFakeTimer(): void
    {
        Configure::write([
            'WebSocket.timers' => ['/Abc/emptyClass'],
        ]);

        $this->expectExceptionMessage(sprintf(
            'All timers must to be a FQN of a class that extends from "%s"!',
            Timer::class
        ));
        ConfigurationReader::reloadInstance();
        ConfigurationReader::getInstance();
    }

    /**
     * @return void
     */
    public function testInvalidTimerType(): void
    {
        Configure::write([
            'WebSocket.timers' => [Application::class],
        ]);

        $this->expectExceptionMessage(sprintf(
            'All timers must to be a extension from "%s"!',
            Timer::class
        ));
        ConfigurationReader::reloadInstance();
        ConfigurationReader::getInstance();
    }

    /**
     * @return void
     */
    public function testTimer(): void
    {
        Configure::write([
            'WebSocket.timers' => [TestTimer::class],
        ]);
        ConfigurationReader::reloadInstance();
        $instance = ConfigurationReader::getInstance();
        $this->assertInstanceOf(ConfigurationReader::class, $instance);
    }
}
