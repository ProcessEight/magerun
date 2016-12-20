<?php

namespace sfrost2004\Magento\Command\Developer\EmailTemplate;

use N98\Magento\Command\AbstractMagentoCommand;
use N98\Util\Exec;
use N98\Util\OperatingSystem;
use Exception;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\Console\Helper\DialogHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Input\InputOption;

class PreviewCommand extends AbstractMagentoCommand
{
	protected $_appEmulation;

	protected $_initialEnvironmentInfo;

	protected function configure()
	{
		$this
			->setName('dev:email-template:preview')
			->addArgument('template-code', InputArgument::OPTIONAL, 'An email template to preview.')
			->addOption(
				'output',
				null,
				InputOption::VALUE_OPTIONAL,
				'Output method (stdout, log, browser)',
				'stdout'
			)
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

		try {

			// Ask for email template variables
			$variables = $this->_getTransactionalEmailTemplateVariables($output, $templateCode);

			// Start store emulation
			$this->_startStoreEmulation($variables['store_id']);

			// Prepare template variables
			$variables = $this->_prepareTemplateVariables($variables);

			if(!empty($variables['order'])) {
				$order = $variables['order'];

				// Retrieve corresponding email template id and customer name
				if ($order->getCustomerIsGuest()) {
					$templateId = \Mage::getStoreConfig(\Mage_Sales_Model_Order::XML_PATH_EMAIL_GUEST_TEMPLATE, $variables['store_id']);
//					$customerName = $order->getBillingAddress()->getName();
				} else {
					$templateId = \Mage::getStoreConfig(\Mage_Sales_Model_Order::XML_PATH_EMAIL_TEMPLATE, $variables['store_id']);
//					$customerName = $order->getCustomerName();
				}
			}


			/** @var $mailer Mage_Core_Model_Email_Template_Mailer */
			$mailer = \Mage::getModel('core/email_template_mailer');

			// Set all required params and send emails
			$mailer->setStoreId($variables['store_id']);
			$mailer->setTemplateId($templateId);
			$mailer->setTemplateParams(array(
				'order'        => $order,
				'billing'      => $variables['billing'],
				'payment_html' => $variables['payment_html'],
			));

			/** @var $emailTemplate Mage_Core_Model_Email_Template */
			$emailTemplate = \Mage::getModel('core/email_template');

			// Set required design parameters
			$emailTemplate->setDesignConfig(array(
				'area' => 'frontend',
				'store' => $variables['store_id'],
			));

			if (($variables['store_id'] === null) && $emailTemplate->getDesignConfig()->getStore()) {
				$variables['store_id'] = $emailTemplate->getDesignConfig()->getStore();
			}

			if (is_numeric($templateId)) {
				$emailTemplate->load($templateId);
			} else {
				$localeCode = \Mage::getStoreConfig('general/locale/code', $variables['store_id']);
				$emailTemplate->loadDefault($templateId, $localeCode);
			}

			if (!$emailTemplate->getId()) {
				throw \Mage::exception('Mage_Core', \Mage::helper('core')->__('Invalid transactional email code: %s', $templateId));
			}

			$emailTemplate->setUseAbsoluteLinks(true);
			$processedTemplate = $emailTemplate->getProcessedTemplate($variables, true);
			$subject = $emailTemplate->getProcessedTemplateSubject($variables);

			// Stop store emulation
			$this->_stopStoreEmulation();

			// Refactor to method eventually
			if($input->getOption('output') == 'log') {

				$filename = 'email-' . date('Ymd-His') . '.log';

				\Mage::log("SUBJECT: {$subject} \n\n{$processedTemplate}", \Zend_Log::DEBUG, $filename, true);

				$message = '<info>Wrote to log file ';
				$message .= \Mage::getBaseDir('var') . DIRECTORY_SEPARATOR . 'log' . DIRECTORY_SEPARATOR . $filename;
				$message .= '</info>';

				$output->writeln($message);

			} elseif($input->getOption('output') == 'html') {

				$filename = 'email-' . date('Ymd-His') . '.html';
				\Mage::log("SUBJECT: {$subject} \n\n{$processedTemplate}", \Zend_Log::DEBUG, $filename, true);

				$message = '<info>Wrote to HTML file ';
				$message .= \Mage::getBaseDir('var') . DIRECTORY_SEPARATOR . 'log' . DIRECTORY_SEPARATOR . $filename;
				$message .= '</info>';

				$output->writeln($message);

			} elseif($input->getOption('output') == 'browser') {

				// Create new folder in media folder
				$file = new \Varien_Io_File();
				$emailPreviewFolderPath = \Mage::getBaseDir(\Mage_Core_Model_Store::URL_TYPE_MEDIA) .
				                          DIRECTORY_SEPARATOR . 'email_preview' . DIRECTORY_SEPARATOR;
				$emailPreviewFolderResult = $file->checkAndCreateFolder($emailPreviewFolderPath);

				if (!$emailPreviewFolderResult) {
					throw new RuntimeException("Could not create folder {$emailPreviewFolderPath}");
				}

				// Write to media folder, so browser can access it
				$filename = 'email-' . date('Ymd-His') . '.html';
				if (!$file->write($emailPreviewFolderPath . $filename, $processedTemplate)) {
					throw new RuntimeException("Could not save email preview to {$emailPreviewFolderPath}{$filename}");
				}

				// Open email template in browser
				$url = \Mage::getBaseUrl(\Mage_Core_Model_Store::URL_TYPE_MEDIA) .
				       'email_preview' . DIRECTORY_SEPARATOR . $filename;

				$output->writeln("<info>Opening {$url} in browser</info>");
				$opener = $this->resolveOpenerCommand($output);
				Exec::run(escapeshellcmd($opener . ' ' . $url));

			} else {
				$output->writeln("SUBJECT: {$subject} \n\n{$processedTemplate}");
			}

		} catch (Exception $e) {
			$output->writeln('<error>' . $e->getMessage() . '</error>');
		}
	}

	/**
	 * @return array
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
	 * Start store emulation process
	 *
	 * @param int $variables['store_id']
	 *
	 * @return Varien_Object information about environment of the initial store
	 */
	protected function _startStoreEmulation($storeId) {

		/** @var $appEmulation Mage_Core_Model_App_Emulation */
		$appEmulation = \Mage::getSingleton('core/app_emulation');
		$initialEnvironmentInfo = $appEmulation->startEnvironmentEmulation($storeId);

		$this->_appEmulation            = $appEmulation;
		$this->_initialEnvironmentInfo  = $initialEnvironmentInfo;
	}

	/**
	 * Stop enviromment emulation
	 *
	 * Function restores initial store environment
	 *
	 * @internal Varien_Object $initialEnvironmentInfo information about environment of the initial store
	 */
	protected function _stopStoreEmulation() {

		$this->_appEmulation->stopEnvironmentEmulation($this->_initialEnvironmentInfo);
	}

	/**
	 * @param OutputInterface $output
	 * @param                 $templateCode
	 *
	 * @return array
	 */
	protected function _getTransactionalEmailTemplateVariables(OutputInterface $output, $templateCode) {

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
//			$email = $dialog->ask(
//				$output,
//				'<question>Customer Email (customer@example.com):</question>: '
//			);
//
//			if (empty($email)) {
//				$email = 'customer@example.com';
//			}
//
//			// Get Name
//			$name = $dialog->ask(
//				$output,
//				'<question>Customer Name (John Smith):</question>: '
//			);
//
//			if (empty($name)) {
//				$name = 'John Smith';
//			}

			$variables = [
				'order_increment_id'    => $orderIncrementId,
				'store_id'              => $storeId,
//				'email'                 => $email,
//				'name'                  => $name,
			];

		}

		return $variables;
	}

	/**
	 * @param array $variables
	 *
	 * @return array
	 * @throws Exception
	 */
	protected function _prepareTemplateVariables(array $variables) {

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
				$this->_appEmulation->stopEnvironmentEmulation($this->_initialEnvironmentInfo);
				throw $exception;
			}
		} else {
			$order              = '';
			$billing            = '';
			$paymentBlockHtml   = '';
		}

		$variables = array_merge($variables, [
			'order'         => $order,
			'billing'       => $billing,
			'payment_html'  => $paymentBlockHtml,
			'store'         => $store,
//			'email'         => $variables['email'],
//			'name'          => $variables['name'],
		]);

		return $variables;
	}

	/**
	 * Generate preview of email template
	 *
	 * @return string
	 */
	protected function _generatePreview($templateCode, $variables) {

		$templateCode = 1;

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

		$variables['this'] = $template;

		$templateProcessed = $template->getProcessedTemplate($variables, true);

		if ($template->isPlain()) {
			$templateProcessed = "<pre>" . htmlspecialchars($templateProcessed) . "</pre>";
		}

		return $templateProcessed;
	}

	/**
	 * @param OutputInterface $output
	 * @return string
	 */
	private function resolveOpenerCommand(OutputInterface $output)
	{
		$opener = '';
		if (OperatingSystem::isMacOs()) {
			$opener = 'open';
		} elseif (OperatingSystem::isWindows()) {
			$opener = 'start';
		} else {
			// Linux
			if (exec('which xdg-open')) {
				$opener = 'xdg-open';
			} elseif (exec('which gnome-open')) {
				$opener = 'gnome-open';
			} elseif (exec('which kde-open')) {
				$opener = 'kde-open';
			}
		}

		if (empty($opener)) {
			throw new RuntimeException('No opener command like xdg-open, gnome-open, kde-open was found.');
		}

		if (OutputInterface::VERBOSITY_DEBUG <= $output->getVerbosity()) {
			$message = sprintf('open command is "%s"', $opener);
			$output->writeln(
				'<debug>' . $message . '</debug>'
			);
		}

		return $opener;
	}
}
