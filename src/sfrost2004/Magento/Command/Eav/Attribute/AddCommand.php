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

namespace sfrost2004\Magento\Command\Eav\Attribute;

use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Exception;

class AddCommand extends AbstractMagentoCommand
{
	protected function configure()
	{
		$this
			->setName('eav:attribute:add')
			->addArgument('entityType', InputArgument::REQUIRED, 'Entity Type Code like catalog_product')
			->addArgument('attributeCode', InputArgument::REQUIRED, 'Attribute Code')
			->setDescription('Creates resource script to add a new attribute to EAV entity [sfrost2004]');
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
			$entityType = $input->getArgument('entityType');
			$attributeCode = $input->getArgument('attributeCode');

			$generator = EntityType\Factory::create($entityType, $attributeCode);
			$code = $generator->generateCode();
			$warnings = $generator->getWarnings();

			$output->writeln(implode(PHP_EOL, $warnings) . PHP_EOL . $code);
		} catch (Exception $e) {
			$output->writeln('<error>' . $e->getMessage() . '</error>');
		}
	}
}