<?php
declare(strict_types=1);

namespace WebSocket\Test\TestCase;

use Cake\Error\FatalErrorException;
use Cake\Http\ServerRequest;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;
use WebSocket\Utils;

class UtilsTest extends TestCase
{
    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * @return void
     */
    public function testRequestIsFalse(): void
    {
        Router::resetRoutes();
        $this->expectException(FatalErrorException::class);
        Utils::routeToMd5([]);
    }

    /**
     * @return void
     */
    public function testWithQuery(): void
    {
        if (Router::getRequest() === null) {
            Router::setRequest(new ServerRequest(['base' => '', 'url' => '', 'webroot' => '/']));
        }
        $query = ['xyz' => 2, 'abc' => '1'];
        ksort($query);
        $queryMd5 = md5(strval(json_encode($query)));

        $hash = Utils::routeToMd5(['?' => $query], false, false);
        $this->assertStringMatchesFormat($queryMd5, explode('.', $hash)[2]);
    }

    /**
     * @return void
     */
    public function testIgnoring(): void
    {
        if (Router::getRequest() === null) {
            Router::setRequest(new ServerRequest(['base' => '', 'url' => '', 'webroot' => '/']));
        }

        $hash = Utils::routeToMd5([], true, false);
        $this->assertStringMatchesFormat('none', explode('.', $hash)[1]);

        $hash = Utils::routeToMd5([], false, true);
        $this->assertStringMatchesFormat('none', explode('.', $hash)[2]);
    }
}
