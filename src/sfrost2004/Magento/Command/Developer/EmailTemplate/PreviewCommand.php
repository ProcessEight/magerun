<?php

namespace sfrost2004\Magento\Command\Developer\EmailTemplate;

use Exception;
use InvalidArgumentException;
use Symfony\Component\Console\Helper\DialogHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\NullOutput;
use N98\Magento\Command\AbstractMagentoCommand;

class PreviewCommand extends AbstractMagentoCommand
{
	protected function configure()
	{
		$this
			->setName('dev:email-template:preview')
			->addArgument('template-code', InputArgument::OPTIONAL, 'An email template to preview.')
			->setDescription('Generates a preview of a transactional email template [sfrost2004]');

		$help = <<<HELP

Generates a preview of a transactional email template [sfrost2004]
   
$ n98-magerun.phar dev:email-template:preview [OPTIONS]

OPTIONS

template-code   Transactional email template code


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

		/*
		 * If no template passed, ask user for one
		 */
//		$this->disableObservers();
		$templateCode = $input->getArgument('template-code');
		if ($templateCode === null) {
			$this->writeSection($output, 'Available transactional email templates');
			$emailTemplatesList = $this->_getTransactionalEmailTemplatesList();
			$question = array();
			foreach ($emailTemplatesList as $key => $templateCode) {
				$question[] = '<comment>' . str_pad('[' . ($key + 1) . ']', 4, ' ', STR_PAD_RIGHT) . '</comment> ' .
				              str_pad($templateCode, 40, ' ', STR_PAD_RIGHT) . "\n";
			}
			$question[] = '<question>Please select a template to preview: </question>';

			/** @var DialogHelper $dialog */
			$dialog = $this->getHelper('dialog');
			$templateCode = $dialog->askAndValidate($output, $question, function ($typeInput) use ($emailTemplatesList) {
				$typeInputs = array($typeInput);

				$returnCode = null;
				foreach ($typeInputs as $typeInput) {
					if (!isset($emailTemplatesList[$typeInput - 1])) {
						throw new InvalidArgumentException('Template not found');
					}

					$returnCode = $emailTemplatesList[$typeInput - 1];
				}

				return $returnCode;
			});
		}

		if($templateCode == 1) {
			// Get Order Increment ID
			/** @var DialogHelper $dialog */
			$dialog = $this->getHelper('dialog');
			$orderIncrementId = $dialog->ask(
				$output,
				'<question>Order Increment ID:</question>: '
			);

			if (empty($orderIncrementId)) {
				$orderIncrementId = '';
			}

			// Get Store ID
			$storeId = $dialog->ask(
				$output,
				'<question>Store ID (1):</question>: '
			);

			if (empty($storeId)) {
				$storeId = 1;
			}

			// Get Email
			$email = $dialog->ask(
				$output,
				'<question>Customer Email (customer@example.com):</question>: '
			);

			if (empty($email)) {
				$email = 'customer@example.com';
			}

			// Get Name
			$name = $dialog->ask(
				$output,
				'<question>Customer Name (John Smith):</question>: '
			);

			if (empty($name)) {
				$name = 'John Smith';
			}

			$variables = [
				'order_increment_id'    => $orderIncrementId,
				'store_id'              => $storeId,
				'email'                 => $email,
				'name'                  => $name,
			];

		}

		try {

			$processedTemplate = $this->_generatePreview($templateCode, $variables);

			$output->writeln($processedTemplate);

		} catch (Exception $e) {
			$output->writeln('<error>' . $e->getMessage() . '</error>');
		}
	}

	/**
	 * @param OutputInterface $output
	 */
	protected function _getTransactionalEmailTemplatesList()
	{
		$templateCodes = [];
		$templates = \Mage::app()->getConfig()->getNode('default/carriers');
		foreach ($templates->children() as $template) {
			/** @var \Mage_Core_Model_Config_Element $template */
			$templateCodes[] = array(
				'code'   => $template->getName(),
				'title'  => (string)$template->title,
			);
		}

		return $templateCodes;
	}

	/**
	 * Generate preview of email template
	 *
	 * @return string
	 */
	protected function _generatePreview($templateCode, $variables) {

		$templateCode = 1;

		// Start store emulation process
		// Since the Transactional Email preview process has no mechanism for selecting a store view to use for
		// previewing, use the default store view
		$appEmulation = \Mage::getSingleton('core/app_emulation');
		$initialEnvironmentInfo = $appEmulation->startEnvironmentEmulation($variables['store_id']);

		/** @var $template Mage_Core_Model_Email_Template */
		$template = \Mage::getModel('core/email_template');
		$id = $templateCode;
		if ($id) {
			$template->load($id);
		} else {
			throw new Exception('Could not load template for template code ' . $templateCode);
		}

		/* @var $filter Mage_Core_Model_Input_Filter_MaliciousCode */
		$filter = \Mage::getSingleton('core/input_filter_maliciousCode');

		$template->setTemplateText(
			$filter->filter($template->getTemplateText())
		);

		$store   = \Mage::app()->getStore($variables['store_id']);
		if(!empty($variables['order_increment_id'])) {
			$order              = \Mage::getModel('sales/order')->loadByIncrementId($variables['order_increment_id']);
			$billing            = $order->getBillingAddress();

			try {
				// Retrieve specified view block from appropriate design package (depends on emulated store)
				$paymentBlock = \Mage::helper('payment')->getInfoBlock($order->getPayment())->setIsSecureMode(true);
				$paymentBlock->getMethod()->setStore($store->getId());
				$paymentBlockHtml = $paymentBlock->toHtml();
			} catch (Exception $exception) {
				// Stop store emulation process
				$appEmulation->stopEnvironmentEmulation($initialEnvironmentInfo);
				throw $exception;
			}
		} else {
			$order              = '';
			$billing            = '';
			$paymentBlockHtml   = '';
		}

		$variables = [
			'order'         => $order,
			'billing'       => $billing,
			'payment_html'  => $paymentBlockHtml,
			'store'         => $store,
			'email'         => $variables['email'],
			'name'          => $variables['name'],
			'this'          => $template,
		];

		$templateProcessed = $template->getProcessedTemplate($variables, true);

		if ($template->isPlain()) {
			$templateProcessed = "<pre>" . htmlspecialchars($templateProcessed) . "</pre>";
		}

		// Stop store emulation process
		$appEmulation->stopEnvironmentEmulation($initialEnvironmentInfo);

		return $templateProcessed;
	}
}
