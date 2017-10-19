<?php

namespace ProjectEight\Magento\Command\System;

use LogicException;
use N98\Magento\Command\AbstractMagentoCommand;
use N98\Magento\Command\CommandAware;
use N98\Magento\Command\CommandConfigAware;
use ProjectEight\Magento\Command\System\Report\Result;
use ProjectEight\Magento\Command\System\Report\ResultCollection;
use N98\Util\Console\Helper\Table\Renderer\RendererFactory;
use N98\Util\Unicode\Charset;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ReportCommand
 *
 * @package ProjectEight\Magento\Command\System
 */
class ReportCommand extends AbstractMagentoCommand
{
    /**
     * @var int
     */
    const UNICODE_INFO_CHAR = 2139;

    /**
     * Command config
     *
     * @var array
     */
    private $config;

    protected function configure()
    {
        $this
            ->setName('project-eight:sys:report')
            ->setDescription('Interrogates a Magento system and produces a README in markdown format');

        $help = <<<HELP
- Checks missing files and folders
- Security
- PHP Extensions (Required and Bytecode Cache)
- MySQL InnoDB Engine
HELP;
        $this->setHelp($help);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->detectMagento($output);
        if (!$this->initMagento()) {
            return;
        }

        $this->config = $this->getCommandConfig();

        $results = new ResultCollection();

        foreach ($this->config['reports'] as $checkGroup => $checkGroupClasses) {
            $results->setResultGroup($checkGroup);
            foreach ($checkGroupClasses as $checkGroupClass) {
                $this->_invokeCheckClass($results, $checkGroupClass);
            }
        }

        $this->_printResults($output, $results);
    }

    /**
     * @param ResultCollection $results
     * @param string $checkGroupClass name
     */
    protected function _invokeCheckClass(ResultCollection $results, $checkGroupClass)
    {
        $report = $this->_createCheck($checkGroupClass);

        switch (true) {
            case $report instanceof Report\SimpleReport:
                $report->report($results);
                break;

            case $report instanceof Report\StoreReport:
                $this->checkStores($results, $checkGroupClass, $report);
                break;

            case $report instanceof Report\WebsiteReport:
                $this->checkWebsites($results, $checkGroupClass, $report);
                break;

            default:
                throw new LogicException(
                    sprintf('Undefined Report class "%s". Have you added a new switch..case branch on line %d of %s?', $checkGroupClass, __LINE__, __CLASS__)
                );
        }
    }

    /**
     * @param OutputInterface $output
     * @param ResultCollection $results
     */
    protected function _printResults(OutputInterface $output, ResultCollection $results)
    {
        $lastResultGroup = null;
        foreach ($results as $result) {
            if ($result->getResultGroup() != $lastResultGroup) {
                $this->writeSection($output, str_pad(strtoupper($result->getResultGroup()), 60, ' ', STR_PAD_BOTH));
            }
            if ($result->getMessage()) {
                switch ($result->getStatus()) {
                    case Result::STATUS_WARNING:
                    case Result::STATUS_ERROR:
                        $output->write('<error>' . Charset::convertInteger(Charset::UNICODE_CROSS_CHAR) . '</error> ');
                        break;

                    case Result::STATUS_INFO:
//                        $output->write(
//                            '<info>' . Charset::convertInteger(self::UNICODE_INFO_CHAR) . '</info> '
//                        );
                        break;

                    case Result::STATUS_OK:
                    default:
                        $output->write(
                            '<info>' . Charset::convertInteger(Charset::UNICODE_CHECKMARK_CHAR) . '</info> '
                        );
                        break;
                }
                $output->writeln($result->getMessage());
            }

            $lastResultGroup = $result->getResultGroup();
        }
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param ResultCollection $results
     */
    protected function _printTable(InputInterface $input, OutputInterface $output, ResultCollection $results)
    {
        $table = array();
        foreach ($results as $result) {
            /* @var $result Result */
            $table[] = array(
                $result->getResultGroup(),
                strip_tags($result->getMessage()),
                $result->getStatus(),
            );
        }

        $this->getHelper('table')
             ->setHeaders(array('Group', 'Message', 'Result'))
             ->renderByFormat($output, $table, $input->getOption('format'));
    }

    /**
     * @param string $checkGroupClass
     *
     * @return object
     */
    private function _createCheck($checkGroupClass)
    {
        $check = new $checkGroupClass();

        if ($check instanceof CommandAware) {
            $check->setCommand($this);
        }
        if ($check instanceof CommandConfigAware) {
            $check->setCommandConfig($this->config);

            return $check;
        }

        return $check;
    }

    /**
     * @param ResultCollection $results
     * @param string $context
     * @param string $checkGroupClass
     */
    private function _markCheckWarning(ResultCollection $results, $context, $checkGroupClass)
    {
        $result = $results->createResult();
        $result->setMessage(
            '<error>No ' . $context . ' configured to run store check:</error> <comment>' . basename($checkGroupClass) .
            '</comment>'
        );
        $result->setStatus($result::STATUS_WARNING);
        $results->addResult($result);
    }

    /**
     * @param ResultCollection $results
     * @param string $checkGroupClass name
     * @param Check\StoreCheck $check
     */
    private function checkStores(ResultCollection $results, $checkGroupClass, Check\StoreCheck $check)
    {
        if (!$stores = \Mage::app()->getStores()) {
            $this->_markCheckWarning($results, 'stores', $checkGroupClass);
        }
        foreach ($stores as $store) {
            $check->check($results, $store);
        }
    }

    /**
     * @param ResultCollection $results
     * @param string $checkGroupClass name
     * @param Check\WebsiteCheck $check
     */
    private function checkWebsites(ResultCollection $results, $checkGroupClass, Check\WebsiteCheck $check)
    {
        if (!$websites = \Mage::app()->getWebsites()) {
            $this->_markCheckWarning($results, 'websites', $checkGroupClass);
        }
        foreach ($websites as $website) {
            $check->check($results, $website);
        }
    }
}
