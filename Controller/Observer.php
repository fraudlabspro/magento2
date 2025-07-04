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
        if (!$this->scopeConfig->getValue('fraudlabspro/active_display/active', \Magento\Store\Model\ScopeInterface::SCOPE_STORE)) {
            return true;
        }

        $orderId = $order->getIncrementId();

        if (empty($orderId)) {
            $this->_writelog('Order ' . $orderId . ' is empty. Skip for FraudLabs Pro validation.');
            return true;
        }

        $data = 0;

        if (is_null($order->getfraudlabspro_response())) {
            if ($order->getfraudlabspro_response()) {
                $data = $this->_unserialize($order->getfraudlabspro_response());
            }
        } else {
            $data = json_decode($order->getfraudlabspro_response(), true);
        }

        if ($data) {
            $this->_writelog('Order ' . $orderId . ' has been validated. Skip for FraudLabs Pro validation.');
            return true;
        }

        $this->_writelog('FraudLabs Pro validation has started for Order ' . $orderId . '.');
        $apiKey = $this->scopeConfig->getValue('fraudlabspro/active_display/api_key', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $approveStatus = $this->scopeConfig->getValue('fraudlabspro/active_display/approve_status', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $reviewStatus = $this->scopeConfig->getValue('fraudlabspro/active_display/review_status', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $rejectStatus = $this->scopeConfig->getValue('fraudlabspro/active_display/reject_status', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $notificationOn = is_null($this->scopeConfig->getValue('fraudlabspro/active_display/enable_notification_on', \Magento\Store\Model\ScopeInterface::SCOPE_STORE)) ? '' : $this->scopeConfig->getValue('fraudlabspro/active_display/enable_notification_on', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

        $billingAddress = $order->getBillingAddress();

        $ip = (isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '::1');
        $headers = array(
            'HTTP_CF_CONNECTING_IP', 'HTTP_X_REAL_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_INCAP_CLIENT_IP', 'HTTP_X_SUCURI_CLIENTIP'
        );

        foreach ($headers as $header) {
            if (isset($_SERVER[$header]) && filter_var($_SERVER[$header], FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                $ip = $_SERVER[$header];
            }
        }

        // get the data of all ips
        $ip_sucuri = $ip_incap = $ip_cf = $ip_real = $ip_forwarded = '::1';
        $ip_remoteadd = (isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '::1');
        if (isset($_SERVER['HTTP_X_SUCURI_CLIENTIP']) && filter_var($_SERVER['HTTP_X_SUCURI_CLIENTIP'], FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            $ip_sucuri = $_SERVER['HTTP_X_SUCURI_CLIENTIP'];
        }
        if (isset($_SERVER['HTTP_INCAP_CLIENT_IP']) && filter_var($_SERVER['HTTP_INCAP_CLIENT_IP'], FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            $ip_incap = $_SERVER['HTTP_INCAP_CLIENT_IP'];
        }
        if (isset($_SERVER['HTTP_CF_CONNECTING_IP']) && filter_var($_SERVER['HTTP_CF_CONNECTING_IP'], FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            $ip_cf = $_SERVER['HTTP_CF_CONNECTING_IP'];
        }
        if (isset($_SERVER['HTTP_X_REAL_IP']) && filter_var($_SERVER['HTTP_X_REAL_IP'], FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            $ip_real = $_SERVER['HTTP_X_REAL_IP'];
        }
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $xip = trim(current(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])));

            if (filter_var($xip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                $ip_forwarded = $xip;
            }
        }

        if ($ip == '127.0.0.1' || $ip == '::1') {
            if ($ip_sucuri != '::1') {
                $ip = $ip_sucuri;
            } elseif ($ip_incap != '::1') {
                $ip = $ip_incap;
            } elseif ($ip_cf != '::1') {
                $ip = $ip_cf;
            } elseif ($ip_real != '::1') {
                $ip = $ip_real;
            } elseif ($ip_forwarded != '::1') {
                $ip = $ip_forwarded;
            }
        }

        $item_sku = '';
        $qty = 0;
        $items = $order->getAllItems();
        foreach ($items as $item) {
            if ($item->getParentItem()) continue;
            $product_sku = $item->getSku();
            if ($product_sku != '') {
                $product_type = ($item->getProductType() == 'virtual') ? 'virtual' : (($item->getProductType() == 'downloadable') ? 'downloadable' :'physical');
                $item_sku .= $product_sku . ':' . $item->getQtyOrdered() . ':' . $product_type . ',';
            }
            $qty += $item->getQtyOrdered();
        }
        $item_sku = rtrim($item_sku, ',');

        $payment_mode = $order->getPayment()->getMethod();
        if ($payment_mode === 'ccsave') {
            $paymentMode = 'creditcard';
        } elseif ($payment_mode === 'cashondelivery') {
            $paymentMode = 'cod';
        } elseif ($payment_mode === 'paypal_standard' || $payment_mode === 'paypal_express') {
            $paymentMode = 'paypal';
        } else {
            $paymentMode = $payment_mode;
        }

        $queries = array(
            'format' => 'json',
            'key' => $apiKey,
            'ip' => $ip,
            'ip_remoteadd' => $ip_remoteadd,
            'ip_sucuri' => $ip_sucuri,
            'ip_incap' => $ip_incap,
            'ip_forwarded' => $ip_forwarded,
            'ip_cf' => $ip_cf,
            'ip_real' => $ip_real,
            'first_name' => $billingAddress->getFirstname(),
            'last_name' => $billingAddress->getLastname(),
            'bill_addr' => implode(" ", $billingAddress->getStreet()),
            'bill_city' => $billingAddress->getCity(),
            'bill_state' => $billingAddress->getRegion(),
            'bill_country' => $billingAddress->getCountryId(),
            'bill_zip_code' => $billingAddress->getPostcode(),
            'email_domain' => substr($order->getCustomerEmail(), strpos($order->getCustomerEmail(), '@') + 1),
            'email_hash' => $this->_hash($order->getCustomerEmail()),
            'email' => $order->getCustomerEmail(),
            'user_phone' => $billingAddress->getTelephone(),
            'amount' => $order->getBaseGrandTotal(),
            'quantity' => $qty,
            'currency' => $this->_storeManager->getStore()->getCurrentCurrencyCode(),
            'user_order_id' => $orderId,
            'magento_order_id' => $order->getEntityId(),
            'payment_gateway' => $order->getPayment()->getMethod(),
            'payment_mode' => $paymentMode,
            'device_fingerprint' => (isset($_COOKIE['flp_device'])) ? $_COOKIE['flp_device'] : '',
            'flp_checksum' => (isset($_COOKIE['flp_checksum'])) ? $_COOKIE['flp_checksum'] : '',
            'source' => 'magento',
            'source_version' => '2.7.4',
            'items' => $item_sku,
            'coupon_code' => $order->getCouponCode() ? $order->getCouponCode() : '',
            'coupon_amount' => $order->getCouponCode() ? -($order->getDiscountAmount()) : '',
            'coupon_type' => '',
        );

        $shippingAddress = $order->getShippingAddress();

        if ($shippingAddress) {
            $queries['ship_first_name'] = $shippingAddress->getFirstname();
            $queries['ship_last_name'] = $shippingAddress->getLastname();
            $queries['ship_addr'] = implode(" ", $shippingAddress->getStreet());
            $queries['ship_city'] = $shippingAddress->getCity();
            $queries['ship_state'] = $shippingAddress->getRegion();
            $queries['ship_zip_code'] = $shippingAddress->getPostcode();
            $queries['ship_country'] = $shippingAddress->getCountryId();
        }

        $response = $this->_post('https://api.fraudlabspro.com/v2/order/screen', $queries);

        if (is_null($response) === TRUE) {
            return false;
        } else {
            $result = json_decode($response, true);
        }

        $result['ip_address'] = $queries['ip'];
        $result['api_key'] = $apiKey;
        $result['is_phone_verified'] = 'No';

        $order->setfraudlabspro_response(json_encode($result))->save();
        $this->_writelog('FraudLabs Pro validation has been completed for Order ' . $orderId . '. Status: ' . $result['fraudlabspro_status'] . ', Transaction ID: ' . $result['fraudlabspro_id']);
        $this->_writelog('The order state for Order ' . $orderId . ': ' . $order->getState());
        $this->_writelog('The order status for Order ' . $orderId . ': ' . $order->getStatus());

        if ($result['fraudlabspro_status'] == 'APPROVE') {
            switch ($approveStatus) {
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
                    $order->setHoldBeforeState($order->getState());
                    $order->setHoldBeforeStatus($order->getStatus());
                    $order->setState(\Magento\Sales\Model\Order::STATE_HOLDED, true)->save();
                    $order->setStatus(\Magento\Sales\Model\Order::STATE_HOLDED, true)->save();
                    break;
            }
            $this->_writelog('The updated order state for APPROVE Order ' . $orderId . ': ' . $order->getState());
            $this->_writelog('The updated order status for APPROVE Order ' . $orderId . ': ' . $order->getStatus());
        }

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
                    $order->setHoldBeforeState($order->getState());
                    $order->setHoldBeforeStatus($order->getStatus());
                    $order->setState(\Magento\Sales\Model\Order::STATE_HOLDED, true)->save();
                    $order->setStatus(\Magento\Sales\Model\Order::STATE_HOLDED, true)->save();
                    break;
            }
            $this->_writelog('The updated order state for REVIEW Order ' . $orderId . ': ' . $order->getState());
            $this->_writelog('The updated order status for REVIEW Order ' . $orderId . ': ' . $order->getStatus());
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
                    $order->setState(\Magento\Sales\Model\Order::STATE_NEW, true)->save();
                    $order->setStatus('pending', true)->save();
                    if ($order->canCancel()) {
                        $order->cancel()->save();
                    }
                    break;

                case 'holded':
                    $order->setHoldBeforeState($order->getState());
                    $order->setHoldBeforeStatus($order->getStatus());
                    $order->setState(\Magento\Sales\Model\Order::STATE_HOLDED, true)->save();
                    $order->setStatus(\Magento\Sales\Model\Order::STATE_HOLDED, true)->save();
                    break;
            }
            $this->_writelog('The updated order state for REJECT Order ' . $orderId . ': ' . $order->getState());
            $this->_writelog('The updated order status for REJECT Order ' . $orderId . ': ' . $order->getStatus());
        }

        if (((strpos($notificationOn, 'approve') !== FALSE) && $result['fraudlabspro_status'] == 'APPROVE') || ((strpos($notificationOn, 'review') !== FALSE) && $result['fraudlabspro_status'] == 'REVIEW') || ((strpos($notificationOn, 'reject') !== FALSE) && $result['fraudlabspro_status'] == 'REJECT')) {
            // Use zaptrigger API to get zap information
            $zapresponse = $this->_http('https://api.fraudlabspro.com/v2/zaptrigger?' . http_build_query(array(
                'key'        => $apiKey,
                'format'    => 'json',
            )));

            if (is_null($zapresponse) === FALSE) {
                $zapresult = json_decode($zapresponse, true);
                $target_url = $zapresult['target_url'];
            }

            if (!empty($target_url)) {
                $this->_zaphttp($target_url, [
                    'id'           => $result['fraudlabspro_id'],
                    'date_created' => gmdate('Y-m-d H:i:s'),
                    'flp_status'   => $result['fraudlabspro_status'],
                    'full_name'    => $order->getCustomerFirstname() . ' ' . $order->getCustomerLastname(),
                    'email'        => $order->getCustomerEmail(),
                    'order_id'     => $orderId,
                ]);
            }
        }

        $this->messageManager->addSuccess(__('FraudLabs Pro Request sent.'));
        return true;
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

    private function _http($url) {
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

    private function _zaphttp($url, $fields = '') {
        $ch = curl_init();

        if ($fields) {
            $data_string = json_encode($fields);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FAILONERROR, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, '1.1');
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($data_string))
        );

        $response = curl_exec($ch);

        if (!curl_errno($ch)) {
            return $response;
        }

        return false;
    }

    private function _hash($s, $prefix = 'fraudlabspro_') {
        $hash = $prefix . $s;
        for ($i = 0; $i < 65536; $i++)
            $hash = sha1($prefix . $hash);
        return $hash;
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

    private function _writelog($message) {
        if (!$this->scopeConfig->getValue('fraudlabspro/active_display/enable_debug_log', \Magento\Store\Model\ScopeInterface::SCOPE_STORE)) {
            return;
        }
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/FLP-custom.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info($message);
    }

}
