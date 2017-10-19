<?php

namespace ProjectEight\Magento\Command\System\Report\System;

use ProjectEight\Magento\Command\System\Report\Result;
use ProjectEight\Magento\Command\System\Report\ResultCollection;
use ProjectEight\Magento\Command\System\Report\SimpleReport;

/**
 * Class MagentoVersionReport
 *
 * @package ProjectEight\Magento\Command\System\Report\System
 */
class CacheReport implements SimpleReport
{
    /**
     * @param ResultCollection $results
     *
     * @return void
     */
    public function report(ResultCollection $results)
    {
        $result = $results->createResult();

        $cacheBackend = get_class(\Mage::app()->getCache()->getBackend());

        switch ($cacheBackend) {
            case 'Zend_Cache_Backend_File':
                $cacheDir = \Mage::app()->getConfig()->getOptions()->getCacheDir();
                break;

            default:
        }

        $message = "<info>Cache Backend: <comment>$cacheBackend</comment>.</info>\n";
        $message .= "<info>Cache Directory: <comment>$cacheDir</comment>.</info>\n";

        $result->setStatus(Result::STATUS_INFO);
        $result->setMessage($message);
    }

}
