<?php
declare(strict_types=1);

namespace TestApp;

use Cake\Http\BaseApplication;
use Cake\Http\MiddlewareQueue;

class Application extends BaseApplication
{
    /**
     * @param \Cake\Http\MiddlewareQueue $middlewareQueue The middleware queue to set in your App Class
     * @return \Cake\Http\MiddlewareQueue
     */
    public function middleware(MiddlewareQueue $middlewareQueue): MiddlewareQueue
    {
        return $middlewareQueue;
    }

    /**
     * @return void
     */
    public function bootstrap(): void
    {
        parent::bootstrap();

        $this->addPlugin('WebSocket');
    }
}
