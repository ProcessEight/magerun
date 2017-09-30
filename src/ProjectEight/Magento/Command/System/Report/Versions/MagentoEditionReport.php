<?php

namespace ProjectEight\Magento\Command\System\Report\Versions;

use ProjectEight\Magento\Command\System\Report\Result;
use ProjectEight\Magento\Command\System\Report\ResultCollection;
use ProjectEight\Magento\Command\System\Report\SimpleReport;

/**
 * Class MagentoEditionReport
 *
 * @package ProjectEight\Magento\Command\System\Report\Versions
 */
class MagentoEditionReport implements SimpleReport
{
    /**
     * @param ResultCollection $results
     *
     * @return void
     */
    public function report(ResultCollection $results)
    {
        $result = $results->createResult();

        $edition = \Mage::getEdition();

        $result->setStatus(Result::STATUS_INFO);
        $result->setMessage("<info>Edition: <comment>$edition</comment>. </info>");
    }

}
