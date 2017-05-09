<?php

namespace ProjectEight\Magento\Command\Developer\Toggle;

use Exception;
use InvalidArgumentException;
use Symfony\Component\Console\Helper\DialogHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\NullOutput;

class ShippingMethodCommand extends AbstractTogglerCommand
{
	protected function configure()
	{
		$this
			->setName('dev:toggle:shipping-method')
			->addArgument('shipping_method_code', InputArgument::OPTIONAL, 'Code of shipping method.')
			->setDescription('Toggle availability of shipping method');

		$help = <<<HELP
   $ n98-magerun.phar dev:toggle:shipping-method [code]

Enable/disable shipping method by method code. Code is optional. If you don't specify a code you can pick a method from a list.

Updates the config in the default scope only (at the moment).

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
		$this->detectMagento($output, true);
		if (!$this->initMagento()) {
			return;
		}

		$this->writeSection($output, 'Shipping Methods');
		$this->disableObservers();
		$shippingMethodCode = $input->getArgument('shipping_method_code');
		$shippingMethodList = $this->getShippingMethodList();
		if ($shippingMethodCode === null) {
			$question = array();
			foreach ($shippingMethodList as $key => $shippingMethod) {
				$question[] = '<comment>' . str_pad('[' . ($key + 1) . ']', 4, ' ', STR_PAD_RIGHT) . '</comment> ' .
				              str_pad($shippingMethod['code'], 40, ' ', STR_PAD_RIGHT) .
				              str_pad($shippingMethod['title'], 40, ' ', STR_PAD_RIGHT) . "\n";
			}
			$question[] = '<question>Please select a shipping method: </question>';

			/** @var  DialogHelper $dialog */
			$dialog = $this->getHelper('dialog');
			$shippingMethodCode = $dialog->askAndValidate($output, $question, function ($typeInput) use ($shippingMethodList) {
				$typeInputs = array($typeInput);

				$returnCode = null;
				foreach ($typeInputs as $typeInput) {
					if (!isset($shippingMethodList[$typeInput - 1])) {
						throw new InvalidArgumentException('Invalid shipping method');
					}

					$returnCode = $shippingMethodList[$typeInput - 1]['code'];
				}

				return $returnCode;
			});
		}

		try {

			/*
			 * Use existing config:set command
			 */

			$input = new StringInput('cache:clean config');

			// ensure that n98-magerun doesn't stop after first command
			$this->getApplication()->setAutoExit(false);

			// without output
			$this->getApplication()->run($input, new NullOutput());

			$newStatus = !\Mage::getStoreConfigFlag('carriers/' . $shippingMethodCode . '/active');

			$input = new StringInput('config:set carriers/flatrate/active ' . (int)$newStatus);
//			$input = new StringInput('config:set --scope websites --scope-id 2 carriers/flatrate/active 0');

			// with output
			$this->getApplication()->run($input, $output);

			// reactivate auto-exit
			$this->getApplication()->setAutoExit(true);

		} catch (Exception $e) {
			$output->writeln('<error>' . $e->getMessage() . '</error>');
		}
	}
}
