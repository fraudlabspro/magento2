<?php

namespace Hexasoft\FraudLabsPro\Block\Adminhtml\Order\View\Tab;

class Flpsummary extends \Magento\Backend\Block\Template implements \Magento\Backend\Block\Widget\Tab\TabInterface
{

    protected $_template = 'order/view/tab/flpsummary.phtml';
    protected $coreRegistry = null;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        array $data = []
    ) {
        $this->coreRegistry = $registry;
        parent::__construct($context, $data);
    }

    public function getOrder()
    {
        return $this->coreRegistry->registry('current_order');
    }

    public function getTabLabel()
    {
        return __('FraudLabs Pro');
    }

    public function getTabTitle()
    {
        return __('FraudLabs Pro');
    }

    public function canShowTab()
    {
        return true;
    }

    public function isHidden()
    {
        return false;
    }

    public function getTabClass()
    {
        return 'ajax only';
    }

    public function getClass()
    {
        return $this->getTabClass();
    }

    public function getTabUrl()
    {
        return $this->getUrl('flpsummarytab/*/flpsummaryTab', ['_current' => true]);
    }
}