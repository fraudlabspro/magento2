<?php
namespace Hexasoft\FraudLabsPro\Model\Adminhtml\System\Config\Source;

class View implements \Magento\Framework\Option\ArrayInterface {
    public function toOptionArray()
    {
        return array(
            array('value' => 'notification_approve', 'label' => __('Approve Status')),
            array('value' => 'notification_review', 'label' => __('Review Status')),
            array('value' => 'notification_reject', 'label' => __('Reject Status')),
        );
    }

    public function toArray()
    {
        return array(
            'notification_approve' => __('Approve Status'),
            'notification_review' => __('Review Status'),
            'notification_reject' => __('Reject Status'),
        );
    }
}