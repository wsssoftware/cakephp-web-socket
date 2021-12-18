<?php
declare(strict_types=1);

namespace WebSocket\View\Helper;

use Authentication\View\Helper\IdentityHelper;
use Cake\Core\Configure;
use Cake\I18n\FrozenTime;
use Cake\Routing\Router;
use Cake\Utility\Security;
use Cake\View\Helper;
use WebSocket\ConfigurationReader;
use WebSocket\Enum\WebSocketProtocol;
use WebSocket\Utils;

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
        $configuration = ConfigurationReader::getInstance();
        $proxy = $configuration->getProxy();
        $initializePayload = $this->getEncryptedInitializePayload();
        if ($proxy !== false && ($configuration->getWebSocketProtocol() === WebSocketProtocol::WSS || $configuration->isForceProxy())) {
            $script = sprintf(
                "CakePHPWebSocket.initialize('%s://%s/%s', '%s', %s);",
                $configuration->getWebSocketProtocol()->getProtocol(),
                $configuration->getHttpHost(),
                $proxy,
                $initializePayload,
                Configure::read('debug') ? 'true' : 'false'
            );
        } else {
            $script = sprintf(
                "CakePHPWebSocket.initialize('%s://%s:%s', '%s', %s);",
                $configuration->getWebSocketProtocol()->getProtocol(),
                $configuration->getHttpHost(),
                $configuration->getPort(),
                $initializePayload,
                Configure::read('debug') ? 'true' : 'false'
            );
        }

        return $this->Html->scriptBlock($script, ['type' => 'text/javascript']);
    }

    /**
     * @return string
     */
    protected function getEncryptedInitializePayload(): string
    {
        $request = $this->getView()->getRequest();
        $routeMd5 = Utils::routeToMd5(Router::parseRequest($request));

        $payload = [
            'sessionId' => $request->getSession()->id(),
            'userId' => null,
            'routeMd5' => $routeMd5,
            'expires' => FrozenTime::now()->modify('+10 seconds'),
        ];

        if (!empty($this->getView()->Identity) && $this->getView()->Identity instanceof IdentityHelper) {
            /** @var IdentityHelper $identity */
            $identity = $this->getView()->Identity;
            $payload['userId'] = $identity->getId();
        }

        return urlencode(Security::encrypt(json_encode($payload), Security::getSalt()));
    }

}
