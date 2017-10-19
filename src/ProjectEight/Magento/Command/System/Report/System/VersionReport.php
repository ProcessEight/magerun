<?php

namespace ProjectEight\Magento\Command\System\Report\System;

use ProjectEight\Magento\Command\System\Report\Result;
use ProjectEight\Magento\Command\System\Report\ResultCollection;
use ProjectEight\Magento\Command\System\Report\SimpleReport;

/**
 * Class VersionReport
 *
 * @package ProjectEight\Magento\Command\System\Report\System
 */
class VersionReport implements SimpleReport
{
    /**
     * @param ResultCollection $results
     *
     * @return void
     */
    public function report(ResultCollection $results)
    {
        $result = $results->createResult();

        $version = \Mage::getVersion();

        $result->setStatus(Result::STATUS_INFO);
        $result->setMessage("<info>Version: <comment>$version</comment>.</info>");
    }

}
