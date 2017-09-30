<?php

namespace ProjectEight\Magento\Command\System\Report;
use N98\Magento\Command\System\Check\{
    ResultCollection
};

/**
 * Interface SimpleReport
 *
 * @package ProjectEight\Magento\Command\System\Report
 */
interface SimpleReport
{
    /**
     * @param ResultCollection $results
     * @return void
     */
    public function report(ResultCollection $results);
}
