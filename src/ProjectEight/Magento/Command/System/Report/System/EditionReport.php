<?php

namespace ProjectEight\Magento\Command\System\Report\System;

use ProjectEight\Magento\Command\System\Report\Result;
use ProjectEight\Magento\Command\System\Report\ResultCollection;
use ProjectEight\Magento\Command\System\Report\SimpleReport;
use N98\Magento\Command\CommandAware;
use Symfony\Component\Console\Command\Command;
use ProjectEight\Magento\Command\System\ReportCommand;

/**
 * Class EditionReport
 *
 * @package ProjectEight\Magento\Command\System\Report\System
 */
class EditionReport implements SimpleReport, CommandAware
{
    /**
     * @var ReportCommand
     */
    protected $reportCommand;

    /**
     * @param ResultCollection $results
     *
     * @return void
     */
    public function report(ResultCollection $results)
    {
        $result = $results->createResult();

        $edition = 'Community';
        if($this->reportCommand->getApplication()->isMagentoEnterprise()) {
            $edition = 'Enterprise';
        }

        $result->setStatus(Result::STATUS_INFO);
        $result->setMessage("<info>Edition: <comment>$edition</comment>. </info>");
    }

    /**
     * @param Command $command
     */
    public function setCommand(Command $command)
    {
        $this->reportCommand = $command;
    }

}
