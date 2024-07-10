<?php

namespace Hexasoft\FraudLabsPro\Controller;

use Magento\Framework\Event\ObserverInterface;

class OrderSaveAfter implements ObserverInterface {

    /**
     * @var ObjectManagerInterface
     */
    protected $_objectManager;
    protected $_storeManager;
    protected $scopeConfig;
    public $messageManager;

    /**
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     */
    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager, \Magento\Store\Model\StoreManagerInterface $storeManager, \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig, \Magento\Framework\Message\ManagerInterface $messageManager) {

        $this->_objectManager = $objectManager;
        $this->_storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
        $this->messageManager = $messageManager;
    }

    /**
     * customer register event handler
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer) {
        return $this->updateOrderStatusSyncFraudLabsPro($observer);
    }

    public function updateOrderStatusSyncFraudLabsPro($observer) {
        if (!$this->scopeConfig->getValue('fraudlabspro/active_display/active', \Magento\Store\Model\ScopeInterface::SCOPE_STORE)) {
            return true;
        }

        if (!$this->scopeConfig->getValue('fraudlabspro/active_display/sync_magento_status', \Magento\Store\Model\ScopeInterface::SCOPE_STORE)) {
            return true;
        }

        $order = $observer->getEvent()->getOrder();
        $orderStatusNew = $order->getState();

        if ($order->getfraudlabspro_response()) {
            if (is_null($order->getfraudlabspro_response())) {
                if ($order->getfraudlabspro_response()) {
                    $data = $this->_unserialize($order->getfraudlabspro_response());
                }
            } else {
                $data = json_decode($order->getfraudlabspro_response(), true);
            }

            $flpId = $data['fraudlabspro_id'] ?? '';
            $apiKey = $this->scopeConfig->getValue('fraudlabspro/active_display/api_key', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

            if ($flpId != '') {
                if ($orderStatusNew == \Magento\Sales\Model\Order::STATE_COMPLETE) {
                    $data['fraudlabspro_status'] = 'APPROVE';

                    $queries= [
                        'key'          => $apiKey,
                        'action'       => 'APPROVE',
                        'id'           => $flpId,
                        'source'       => 'magento',
                        'triggered_by' => 'order_status_completed',
                        'format'       => 'json',
                    ];

                    $this->_post('https://api.fraudlabspro.com/v2/order/feedback', $queries);
                    $order->setfraudlabspro_response(json_encode($data))->save();
                } elseif ($orderStatusNew == \Magento\Sales\Model\Order::STATE_CANCELED) {
                    $data['fraudlabspro_status'] = 'REJECT';

                    $queries= [
                        'key'          => $apiKey,
                        'action'       => 'REJECT',
                        'id'           => $flpId,
                        'source'       => 'magento',
                        'triggered_by'	=> 'order_status_canceled',
                        'format'       => 'json',
                    ];

                    $this->_post('https://api.fraudlabspro.com/v2/order/feedback', $queries);
                    $order->setfraudlabspro_response(json_encode($data))->save();
                }
            }
        }
    }

    private function _post($url, $fields = ''){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FAILONERROR, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate');
        curl_setopt($ch, CURLOPT_HTTP_VERSION, '1.1');
        curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);

        if (!empty($fields)) {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, (is_array($fields)) ? http_build_query($fields) : $fields);
        }

        $response = curl_exec($ch);

        if (!curl_errno($ch)) {
            return $response;
        }

        return false;
    }

    private function _unserialize($data){
        if (class_exists(\Magento\Framework\Serialize\SerializerInterface::class)) {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $serializer = $objectManager->create(\Magento\Framework\Serialize\SerializerInterface::class);
            return $serializer->unserialize($data);
        } else if (class_exists(\Magento\Framework\Unserialize\Unserialize::class)) {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $serializer = $objectManager->create(\Magento\Framework\Unserialize\Unserialize::class);
            return $serializer->unserialize($data);
        }
    }

}
