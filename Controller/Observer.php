<?php

namespace Hexasoft\FraudLabsPro\Controller;

use Magento\Framework\Event\ObserverInterface;

class Observer implements ObserverInterface {

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
        return $this->sendRequestToFraudLabsPro($observer);
    }

    public function sendRequestToFraudLabsPro($observer) {
        $event = $observer->getEvent();
        $order = $event->getOrder();

        if ($order->getfraudlabspro_response()) {
            return true;
        }
        return $this->processSendRequestToFraudLabsPro($order);
    }

    public function sendRequestToFraudLabsProNonObserver($observer) {
        if (!$this->scopeConfig->getValue('fraudlabspro/active_display/active', \Magento\Store\Model\ScopeInterface::SCOPE_STORE)) {
            return true;
        }

        $event = $observer->getEvent();
        $order = $event->getOrder();

        if ($order->getfraudlabspro_response()) {
            $this->messageManager->addError(__('Request already submitted to FraudLabs Pro.'));
            return true;
        }

        return $this->processSendRequestToFraudLabsPro($order);
    }

    public function processSendRequestToFraudLabsPro($order) {
        $orderId = $order->getIncrementId();

        if (empty($orderId))
            return true;

        $data = unserialize($order->getfraudlabspro_response());

        if ($data)
            return true;

        if (isset($_SERVER['DEV_MODE']))
            $_SERVER['REMOTE_ADDR'] = '175.143.8.154';

        $apiKey = $this->scopeConfig->getValue('fraudlabspro/active_display/api_key', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $reviewStatus = $this->scopeConfig->getValue('fraudlabspro/active_display/review_status', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $rejectStatus = $this->scopeConfig->getValue('fraudlabspro/active_display/reject_status', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

        $billingAddress = $order->getBillingAddress();
        $ip = $_SERVER['REMOTE_ADDR'];

        if (isset($_SERVER['HTTP_CF_CONNECTING_IP']) && filter_var($_SERVER['HTTP_CF_CONNECTING_IP'], FILTER_VALIDATE_IP)) {
            $ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
        }

        if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && filter_var($_SERVER['HTTP_X_FORWARDED_FOR'], FILTER_VALIDATE_IP)) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }

        $queries = array(
            'format' => 'json',
            'key' => $apiKey,
            'ip' => $ip,
            'first_name' => $order->getCustomerFirstname(),
            'last_name' => $order->getCustomerLastname(),
            'bill_city' => $billingAddress->getCity(),
            'bill_state' => $billingAddress->getRegion(),
            'bill_country' => $billingAddress->getCountryId(),
            'bill_zip_code' => $billingAddress->getPostcode(),
            'email_domain' => substr($order->getCustomerEmail(), strpos($order->getCustomerEmail(), '@') + 1),
            'email_hash' => $this->_hash($order->getCustomerEmail()),
            'email' => $order->getCustomerEmail(),
            'user_phone' => $billingAddress->getTelephone(),
            'amount' => $order->getBaseGrandTotal(),
            'quantity' => count($order->getAllItems()),
            'currency' => $this->_storeManager->getStore()->getCurrentCurrencyCode(),
            'user_order_id' => $orderId,
            'magento_order_id' => $order->getEntityId(),
            'flp_checksum' => '',
            'source' => 'magento',
            'source_version' => '1.2.0',
        );

        $queries['ship_city'] = $billingAddress->getCity();
        $queries['ship_state'] = $billingAddress->getRegion();
        $queries['ship_zip_code'] = $billingAddress->getPostcode();
        $queries['ship_country'] = $billingAddress->getCountryId();

        $response = $this->http('https://api.fraudlabspro.com/v1/order/screen?' . http_build_query($queries));

        if (is_null($result = json_decode($response, true)) === TRUE)
            return false;

        $result['ip_address'] = $queries['ip'];
        $result['api_key'] = $apiKey;

        $order->setfraudlabspro_response(serialize($result))->save();

        if ($result['fraudlabspro_status'] == 'REVIEW') {

            switch ($reviewStatus) {
                case 'pending':
                    $order->setState(\Magento\Sales\Model\Order::STATE_NEW, true)->save();
                    $order->setStatus('pending', true)->save();
                    break;

                case 'processing':
                    $order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING, true)->save();
                    $order->setStatus(\Magento\Sales\Model\Order::STATE_PROCESSING, true)->save();
                    break;

                case 'complete':
                    $order->setState(\Magento\Sales\Model\Order::STATE_COMPLETE, true)->save();
                    $order->setStatus(\Magento\Sales\Model\Order::STATE_COMPLETE, true)->save();
                    break;

                case 'closed':
                    $order->setState(\Magento\Sales\Model\Order::STATE_CLOSED, true)->save();
                    $order->setStatus(\Magento\Sales\Model\Order::STATE_CLOSED, true)->save();
                    break;

                case 'fraud':
                    $order->setState(\Magento\Sales\Model\Order::STATUS_FRAUD, true)->save();
                    $order->setStatus(\Magento\Sales\Model\Order::STATUS_FRAUD, true)->save();
                    break;

                case 'canceled':
                    if ($order->canCancel()) {
                        $order->cancel()->save();
                    }
                    break;

                case 'holded':
                    $order->setState(\Magento\Sales\Model\Order::STATE_HOLDED, true)->save();
                    $order->setStatus(\Magento\Sales\Model\Order::STATE_HOLDED, true)->save();
                    break;
            }
        }

        if ($result['fraudlabspro_status'] == 'REJECT') {

            switch ($rejectStatus) {
                case 'pending':
                    $order->setState(\Magento\Sales\Model\Order::STATE_NEW, true)->save();
                    $order->setStatus('pending', true)->save();
                    break;

                case 'processing':
                    $order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING, true)->save();
                    $order->setStatus(\Magento\Sales\Model\Order::STATE_PROCESSING, true)->save();
                    break;

                case 'complete':
                    $order->setState(\Magento\Sales\Model\Order::STATE_COMPLETE, true)->save();
                    $order->setStatus(\Magento\Sales\Model\Order::STATE_COMPLETE, true)->save();
                    break;

                case 'closed':
                    $order->setState(\Magento\Sales\Model\Order::STATE_CLOSED, true)->save();
                    $order->setStatus(\Magento\Sales\Model\Order::STATE_CLOSED, true)->save();
                    break;

                case 'fraud':
                    $order->setState(\Magento\Sales\Model\Order::STATUS_FRAUD, true)->save();
                    $order->setStatus(\Magento\Sales\Model\Order::STATUS_FRAUD, true)->save();
                    break;

                case 'canceled':
                    if ($order->canCancel()) {
                        $order->cancel()->save();
                    }
                    break;

                case 'holded':
                    $order->setState(\Magento\Sales\Model\Order::STATE_HOLDED, true)->save();
                    $order->setStatus(\Magento\Sales\Model\Order::STATE_HOLDED, true)->save();
                    break;
            }
        }
        $this->messageManager->addSuccess(__('FraudLabs Pro Request sent.'));
        return true;
    }

    private function http($url) {

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_FAILONERROR, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $result = curl_exec($ch);

        if (!curl_errno($ch))
            return $result;

        curl_close($ch);

        return false;
    }

    private function _hash($s, $prefix = 'fraudlabspro_') {
        $hash = $prefix . $s;
        for ($i = 0; $i < 65536; $i++)
            $hash = sha1($prefix . $hash);
        return $hash;
    }

}
