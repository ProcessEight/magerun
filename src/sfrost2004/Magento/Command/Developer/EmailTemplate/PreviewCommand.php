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
   $ n98-magerun.phar dev:email:template:preview

Generates a preview of a transactional email template [sfrost2004]

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
			$emailTemplatesList = $this->getTransactionalEmailTemplatesList();
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



		} catch (Exception $e) {
			$output->writeln('<error>' . $e->getMessage() . '</error>');
		}
	}

	/**
	 * @param OutputInterface $output
	 */
	protected function getTransactionalEmailTemplatesList()
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

}
