<?php

namespace ProjectEight\Magento\Command\System\Report\Versions;

use N98\Magento\Command\System\Check\Result;
use N98\Magento\Command\System\Check\ResultCollection;
use ProjectEight\Magento\Command\System\Report\SimpleReport;

/**
 * Class MagentoVersionReport
 *
 * @package ProjectEight\Magento\Command\System\Report\Versions
 */
class MagentoVersionReport implements SimpleReport
{
    protected $latestMagentoVersion = '1.9.3.4';
    /**
     * @param ResultCollection $results
     *
     * @return void
     */
    public function report(ResultCollection $results)
    {
        $result = $results->createResult();

        $version = \Mage::getVersion();

        if(version_compare($this->latestMagentoVersion, $version) <= 0) {
            $result->setStatus(Result::STATUS_OK);
            $result->setMessage("<info>Magento: <comment>$version</comment>. Magento is up-to-date.</info>");
        } else {
            $result->setStatus(Result::STATUS_WARNING);
            $result->setMessage("<info>Magento version: <comment>$version</comment>. Magento is out-of-date.</info>");
        }

    }

}
