<?php

namespace Hexasoft\FraudLabsPro\Block\Adminhtml\Order\Grid\Renderer;

use Magento\Framework\DataObject;

class Risk extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer {

    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager) {

        $this->_objectManager = $objectManager;
    }

    public function render(DataObject $row) {
        $out = '';

        $order = $this->_objectManager->create('sales/order')->load($row->getId());
        $result = $order->getfraudlabspro_response();

        if (!$result) {
            $out = '-';
        } else {
            if(is_null(json_decode($result, true))){
                $data = $this->_unserialize($result);
            } else {
                $data = json_decode($result, true);
            }
            $out .= $data['fraudlabspro_status'];
        }
        return $out;
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
