<?php

namespace sfrost2004\Magento\Command\Developer\Toggle;

use Exception;
use InvalidArgumentException;
use Symfony\Component\Console\Helper\DialogHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Input\InputOption;

class PaymentMethodCommand extends AbstractTogglerCommand
{
	protected function configure()
	{
		$this
			->setName('dev:toggle:payment-method')
			->addArgument('payment_method_code', InputArgument::OPTIONAL, 'Code of payment method.')
			->addArgument('website', InputArgument::OPTIONAL, 'Website')
			->addOption(
				'scope',
				null,
				InputOption::VALUE_OPTIONAL,
				'The config value\'s scope (default, websites, stores)',
				'default'
			)
			->addOption('scope-id', null, InputOption::VALUE_OPTIONAL, 'The config value\'s scope ID')
			->setDescription('Toggle availability of payment method');

		$help = <<<HELP

Enable/disable payment method by method code. Code is optional. If you don't specify a code you can pick a method from a list.

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

		// Ask for config scope
		$scope = $input->getOption('scope');
		$scopeId = $input->getOption('scope-id');

		if($scopeId === null) {
			// Ask for website/store id as appropriate
			// If there is only one store/website, it won't bother asking
			switch ($scope) {
				case 'stores' :
					$scopeIdObject = $this->getHelper('parameter')->askStore($input, $output);
					$scopeId = $scopeIdObject->getId();
					break;

				case 'websites' :
					$scopeIdObject = $this->getHelper('parameter')->askWebsite($input, $output);
					$scopeId = $scopeIdObject->getId();
					break;

				case 'default' :
				default:
					$scopeId = 0;
					break;
			}
		}

		$this->disableObservers();
		$paymentMethodCode = $input->getArgument('payment_method_code');
		$paymentMethodList = $this->getShippingMethodList();
		if ($paymentMethodCode === null) {
			$question = array();
			foreach ($paymentMethodList as $key => $paymentMethod) {
				$question[] = '<comment>' . str_pad('[' . ($key + 1) . ']', 4, ' ', STR_PAD_RIGHT) . '</comment> ' .
				              str_pad($paymentMethod['code'], 40, ' ', STR_PAD_RIGHT) .
				              str_pad($paymentMethod['title'], 40, ' ', STR_PAD_RIGHT) . "\n";
			}
			$question[] = '<question>Please select a payment method: </question>';

			/** @var DialogHelper $dialog */
			$dialog = $this->getHelper('dialog');
			$paymentMethodCode = $dialog->askAndValidate($output, $question, function ($typeInput) use ($paymentMethodList) {
				$typeInputs = array($typeInput);

				$returnCode = null;
				foreach ($typeInputs as $typeInput) {
					if (!isset($paymentMethodList[$typeInput - 1])) {
						throw new InvalidArgumentException('Invalid payment method');
					}

					$returnCode = $paymentMethodList[$typeInput - 1]['code'];
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

			switch($scope) {
				// Payment methods active flag can be set on a website level but not a store level,
				// which Mage::getStoreConfigFlag() should, but doesn't, take into account
				case 'websites' :
					$newStatus = !(int)\Mage::getConfig()->getNode("websites/{$scopeIdObject->getCode()}/payment/{$paymentMethodCode}/active");
					$scopeId = $scopeIdObject->getId();
					break;

				case 'stores' :
					// Need to refactor this because you can't set payment method active flag on store level
					throw new Exception("Cannot enable/disable payment method on store level");
					break;

				default:
					$newStatus = !\Mage::getStoreConfigFlag('payment/' . $paymentMethodCode . '/active');
					break;
			}

			$input = new StringInput( "config:set --scope {$scope} --scope-id {$scopeId} payment/{$paymentMethodCode}/active " . (int)$newStatus);
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
