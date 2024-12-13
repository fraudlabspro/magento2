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
    protected $unserialize;

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
                    if(is_null(json_decode($item[$this->getData('name')], true))){
                        if($item[$this->getData('name')]){
                            $data = $this->_unserialize($item[$this->getData('name')]);
                            $item[$this->getData('name')] = ($data['fraudlabspro_status'] ?? "");
                        }
                    } else {
                        $data = json_decode($item[$this->getData('name')], true);
                        $item[$this->getData('name')] = ($data['fraudlabspro_status'] ?? "");
                    }
                } else {
                    $item[$this->getData('name')] = "";
                }
            }
        }
        return $dataSource;
    }

    private function _unserialize($data){
        if (class_exists(\Magento\Framework\Serialize\SerializerInterface::class)) {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $serializer = $objectManager->create(\Magento\Framework\Serialize\SerializerInterface::class);
            return $serializer->unserialize($data);
        } else if (class_exists(\Magento\Framework\Unserialize\Unserialize::class)) {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $serializer = $objectManager->create(\Magento\Framework\Unserialize\Unserialize::class);
			// if (!empty($data)) {
				// $res = $serializer->unserialize($data);
				// return $res;
			// } else {
				// $data['fraudlabspro_status'] = "";
				// return $data;
			// }
            return $serializer->unserialize($data);
        }
    }

}
