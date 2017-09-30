<?php

namespace ProjectEight\Magento\Command\System\Report;

/**
 * Interface SimpleReport
 *
 * @package ProjectEight\Magento\Command\System\Report
 */
interface SimpleReport
{
    /**
     * @param ResultCollection $results
     *
     * @return void
     */
    public function report(ResultCollection $results);
}
