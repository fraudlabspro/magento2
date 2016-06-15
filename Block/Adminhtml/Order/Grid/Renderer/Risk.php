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
            $data = unserialize($result);
            $out .= $data['fraudlabspro_status'];
        }
        return $out;
    }

}
