<?php

namespace ProjectEight\Magento\Command\Developer\Module\RewriteList;

use N98\Magento\Command\Developer\Module\Rewrite\AbstractRewriteCommand;
use N98\Util\Console\Helper\Table\Renderer\RendererFactory;
use N98\Util\Console\Helper\TableHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\BufferedOutput;

class BetterCommand extends AbstractRewriteCommand
{
    protected function configure()
    {
        $this
            ->setName('dev:module:rewrite:list:better')
            ->setDescription('Lists all magento rewrites, with extra info')
            ->addOption(
                'format',
                NULL,
                InputOption::VALUE_OPTIONAL,
                'Output Format. One of [' . implode(',', RendererFactory::getFormats()) . ']'
            )
        ;
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->detectMagento($output, true);
        if (!$this->initMagento()) {
            return;
        }

        $table = array();
        foreach ($this->loadRewrites() as $type => $data) {
            if (count($data) > 0) {
                foreach ($data as $class => $rewriteClass) {
                    $table[] = array(
                        $type,
                        $class,
                        '',
                        implode(', ', $rewriteClass),
                    );
                }
            }
        }

        try {

            /*
             * Translate group class name
             */

            // ensure that n98-magerun doesn't stop after first command
            $this->getApplication()->setAutoExit(false);

            /*
             * Use existing dev:class:lookup command
             */

            foreach ($table as $row => $rewriteMetadata) {
                if ($rewriteMetadata[0] == "autoload: Mage") {
                    unset($table[ $row ]);
                    continue;
                }

                if (isset($rewriteMetadata[0]) && isset($rewriteMetadata[1])) {
                    $commandOptions = "";
                    $commandOptions .= $rewriteMetadata[0] . " ";                        // type
                    $commandOptions .= explode("\n", $rewriteMetadata[1])[0];   // name

                    // Run command
                    $devClassLookupInput = new StringInput("dev:class:lookup {$commandOptions}");
                    $bufferedOutput      = new BufferedOutput();
                    $this->getApplication()->run($devClassLookupInput, $bufferedOutput);
                    // Get output of command
                    $commandOutput = $bufferedOutput->fetch();
                    // Generate core class name
                    $commandOutput = explode("\n", $commandOutput);
                    $className     = trim(substr($commandOutput[0], strrpos($commandOutput[0], ' ')));
                    $singular      = ucwords(trim($rewriteMetadata[0], 's'));
                    $className     = str_replace(ucwords($rewriteMetadata[0]), $singular, $className);

                    if(substr($className, 0, 15) == "Mage_Enterprise") {
                        $className = str_replace("Mage_", "", $className);
                    }

                    $filepath = "app/code/core/" . str_replace('_', "/", $className) . ".php";

                    $table[ $row ][2] = $className;
                    $table[ $row ][] = $filepath;
                }
            }

            // reactivate auto-exit
            $this->getApplication()->setAutoExit(true);

        } catch (Exception $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
        }

        if (count($table) === 0 && $input->getOption('format') === NULL) {
            $output->writeln('<info>No rewrites were found.</info>');
        } else {
            if (count($table) == 0) {
                $table = array();
            }
            /* @var $tableHelper TableHelper */
            $tableHelper = $this->getHelper('table');
            $tableHelper
                ->setHeaders(array('Type', 'Grouped Class Name', 'Original Class', 'Rewritten Class', 'File path'))
                ->setRows($table)
                ->renderByFormat($output, $table, $input->getOption('format'))
            ;
        }
    }
}
