<?php

namespace sfrost2004\Magento\Command\Developer\Toggle;

use N98\Magento\Command\AbstractMagentoCommand;
use N98\Util\DateTime as DateTimeUtils;

class AbstractTogglerCommand extends AbstractMagentoCommand
{
    /**
     * @return Mage_Index_Model_Indexer
     */
    protected function _getIndexerModel()
    {
        return $this->_getModel('index/indexer', 'Mage_Index_Model_Indexer');
    }

    /**
     * @return array
     */
    protected function getShippingMethodList()
    {
        $list = array();
	    $carriers = \Mage::app()->getConfig()->getNode('default/carriers');
        foreach ($carriers->children() as $carrier) {
        	/** @var \Mage_Core_Model_Config_Element $carrier */
            $list[] = array(
                'code'   => $carrier->getName(),
                'title'  => (string)$carrier->title,
            );
        }

        return $list;
    }

    /**
     * Disable observer which try to create adminhtml session on CLI
     */
    protected function disableObservers()
    {
        $node = \Mage::app()->getConfig()->getNode('adminhtml/events/core_locale_set_locale/observers/bind_locale');
        if ($node) {
            $node->appendChild(new \Varien_Simplexml_Element('<type>disabled</type>'));
        }
    }
}
