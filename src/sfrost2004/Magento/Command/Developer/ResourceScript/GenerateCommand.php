<?php
/**
 * sfrost2004
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future. If you wish to customize this module for your
 * needs please contact Zone8 for more information.
 *
 * @category    sfrost2004
 * @package     sfrost2004
 * @copyright   Copyright (c) 2016 sfrost2004
 * @author      Simon Frost, sfrost2004
 *
 */

namespace sfrost2004\Magento\Command\Developer\ResourceScript;

use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Exception;

class GenerateCommand extends AbstractMagentoCommand
{
	protected function configure()
	{
		$this
			->setName('dev:resource-script:generate')
			->addArgument('type', InputArgument::REQUIRED, 'The type of script to generate, i.e. cms_block to add a CMS static block')
			->addArgument('id', InputArgument::REQUIRED, 'Entity ID to base the resource script on')
			->setDescription('Creates resource script based on the given entity ID [sfrost2004]');
	}

	/**
	 * @param InputInterface  $input
	 * @param OutputInterface $output
	 *
	 * @return int|null|void
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$this->detectMagento($output, true);
		if (!$this->initMagento()) {
			return;
		}

		try {
			$type = $input->getArgument('type');
			$id = $input->getArgument('id');

			$generator = Type\Factory::create($type, $id);
			$code = $generator->generateCode();
			$warnings = $generator->getWarnings();

			$output->writeln(implode(PHP_EOL, $warnings) . PHP_EOL . $code);
		} catch (Exception $e) {
			$output->writeln('<error>' . $e->getMessage() . '</error>');
		}
	}
}