<?php
declare(strict_types=1);

namespace WebSocket\View\Helper;

use Cake\Core\Configure;
use Cake\Routing\Router;
use Cake\View\Helper;
use WebSocket\ConfigurationReader;

/**
 * WebSocket helper
 * @property \Cake\View\Helper\HtmlHelper $Html
 */
class WebSocketHelper extends Helper
{

    /**
     * @var string[]
     */
    protected $helpers = ['Html'];

    /**
     * Default configuration.
     *
     * @var array
     */
    protected $_defaultConfig = [];

    /**
     * @return string
     */
    public function connect(): string
    {
        $request = $this->getView()->getRequest();

        $configuration = ConfigurationReader::getInstance();
        $proxy = $configuration->getProxy();
        if ($proxy !== false) {
            $script = sprintf(
                "CakePHPWebSocket.connect('%s://%s/%s', '%s', '%s', %s);",
                $configuration->getWebSocketProtocol()->getProtocol(),
                $configuration->getHttpHost(),
                $proxy,
                $request->getSession()->id(),
                json_encode(Router::parseRequest($request)),
                Configure::read('debug') ? 'true' : 'false'
            );
        } else {
            $script = sprintf(
                "CakePHPWebSocket.connect('%s://%s:%s', '%s', '%s', %s);",
                $configuration->getWebSocketProtocol()->getProtocol(),
                $configuration->getHttpHost(),
                $configuration->getPort(),
                $request->getSession()->id(),
                json_encode(Router::parseRequest($request)),
                Configure::read('debug') ? 'true' : 'false'
            );
        }

        return $this->Html->scriptBlock($script, ['type' => 'text/javascript']);
    }

}
