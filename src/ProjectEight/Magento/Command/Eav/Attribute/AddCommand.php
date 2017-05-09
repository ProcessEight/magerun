<?php
/**
 * ProjectEight
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future. If you wish to customize this module for your
 * needs please contact ProjectEight for more information.
 *
 * @category    ProjectEight
 * @package     ProjectEight
 * @copyright   Copyright (c) 2016 ProjectEight
 * @author      Simon Frost, ProjectEight
 *
 */

namespace ProjectEight\Magento\Command\Eav\Attribute;

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
			->addArgument('entityType', InputArgument::REQUIRED, 'Entity type code like catalog_product')
			->addArgument('attributeCode', InputArgument::REQUIRED, 'Attribute code')
			->addArgument('frontendInput', InputArgument::OPTIONAL, 'Frontend input type (text, dropdown, multiselect, etc)')
			->setDescription('Creates resource script to add a new attribute to EAV entity [ProjectEight]');
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
			$entityType     = $input->getArgument('entityType');
			$attributeCode  = $input->getArgument('attributeCode');
			$frontendInput  = $input->getArgument('frontendInput');

			$generator = EntityType\Factory::create( $entityType, $attributeCode, $frontendInput);
			$code = $generator->generateCode();
			$warnings = $generator->getWarnings();

			$output->writeln(implode(PHP_EOL, $warnings) . PHP_EOL . $code);
		} catch (Exception $e) {
			$output->writeln('<error>' . $e->getMessage() . '</error>');
		}
	}
}