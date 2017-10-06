<?php

namespace ProjectEight\Magento\Command\System\Report\Shipping;

use N98\Magento\Command\CommandAware;
use ProjectEight\Magento\Command\System\Report\Result;
use ProjectEight\Magento\Command\System\Report\ResultCollection;
use ProjectEight\Magento\Command\System\Report\SimpleReport;
use Symfony\Component\Console\Command\Command;
use ProjectEight\Magento\Command\System\ReportCommand;

/**
 * Class ShippingMethodsReport
 *
 * @package ProjectEight\Magento\Command\System\Report\Shipping
 */
class ShippingMethodsReport implements SimpleReport, CommandAware
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
        $result->setStatus(Result::STATUS_INFO);

        $table = "";
        foreach ($this->getShippingMethodsList() as $method) {
            $table .= "<info>{$method['title']}</info>: {$method['status']}\n";
        }
        if($table == "") {
            $table = "<info>No shipping methods are enabled</info>";
        }

        $result->setMessage("{$table}");
    }

    /**
     * @param Command $command
     */
    public function setCommand(Command $command)
    {
        $this->reportCommand = $command;
    }

    /**
     * @return array
     */
    protected function getShippingMethodsList()
    {
        $list = [];
        $methods = \Mage::app()->getConfig()->getNode('default/carriers');
        foreach ($methods->children() as $method) {
            /** @var \Mage_Core_Model_Config_Element $method */
            if(!$method->active || !$method->title || $method->active == "0") {
                continue;
            }
            $list[] = [
                'code'   => $method->getName(),
                'title'  => (string)$method->title,
                'status'  => ($method->active == "0") ? '<comment>Disabled</comment>' : '<comment>Enabled</comment>',
            ];
        }

        return $list;
    }
}
