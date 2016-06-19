<?php
/**
 * Frostnet
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future. If you wish to customize this module for your
 * needs please contact Zone8 for more information.
 *
 * @category    Frostnet
 * @package     Frostnet
 * @copyright   Copyright (c) 2016 Frostnet
 * @author      Simon Frost, Frostnet
 *
 */

namespace Frostnet\Magento\Command;

use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Exception;
use N98\Magento\Command\Developer\Setup\Script;
use N98\Magento\Command\Developer\Setup\Script\Attribute\EntityType;

class GenerateInstallerCommand extends AbstractMagentoCommand
{
	protected function configure()
	{
		$this
			->setName('frostnet:attribute')
			->addArgument('entityType', InputArgument::REQUIRED, 'Entity Type Code like catalog_product')
			->addArgument('attributeCode', InputArgument::REQUIRED, 'Attribute Code')
			->setDescription('Creates attribute script for a given attribute code and adds it to a specifed attribute set and group, or the default if none is specifed');
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

			$attribute = $this->getAttribute($entityType, $attributeCode);

			$generator = Attribute\EntityType\Factory::create($entityType, $attribute);
			$generator->setReadConnection(
				$this->_getModel('core/resource', 'Mage_Core_Model_Resource')->getConnection('core_read')
			);
			$code = $generator->generateCode();
			$warnings = $generator->getWarnings();

			$output->writeln(implode(PHP_EOL, $warnings) . PHP_EOL . $code);
		} catch (Exception $e) {
			$output->writeln('<error>' . $e->getMessage() . '</error>');
		}
	}

	/**
	 * @param string $entityType
	 * @param string $attributeCode
	 *
	 * @return mixed
	 */
	protected function getAttribute($entityType, $attributeCode)
	{
		$attribute = $this->_getModel('catalog/resource_eav_attribute', 'Mage_Catalog_Model_Resource_Eav_Attribute')
		                  ->loadByCode($entityType, $attributeCode);

		return $attribute;
	}

}