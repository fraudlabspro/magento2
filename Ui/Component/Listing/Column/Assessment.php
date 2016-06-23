<?php

namespace Hexasoft\FraudLabsPro\Ui\Component\Listing\Column;

use Magento\Framework\Escaper;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Sales\Model\ResourceModel\Order\Status\CollectionFactory;

class Assessment extends Column {

    protected $_resource;
    protected $_scopeConfig;
    protected $escaper;

    public function __construct(
    ContextInterface $context, UiComponentFactory $uiComponentFactory, Escaper $escaper, array $components = [], array $data = []
    ) {
        $this->escaper = $escaper;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource) {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                if (array_key_exists($this->getData('name'), $item)) {
                    $data = unserialize($item[$this->getData('name')]);
                    $item[$this->getData('name')] = $data['fraudlabspro_status'];
                } else {
                    $item[$this->getData('name')] = "";
                }
            }
        }
        return $dataSource;
    }

}
