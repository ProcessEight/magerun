<?php

namespace ProjectEight\Magento\Command\Developer\Environment;

use Exception;
use InvalidArgumentException;
use Symfony\Component\Console\Helper\DialogHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\NullOutput;
use N98\Magento\Command\AbstractMagentoCommand;

class SetCommand extends AbstractMagentoCommand
{
	protected function configure()
	{
		$this
			->setName('dev:env:set')
			->addArgument('env', InputArgument::OPTIONAL, 'An environment to configure.')
			->setDescription('Updates the config to match values set in ~/.n98-magerun.yaml [ProjectEight]');

		$help = <<<HELP
   $ n98-magerun.phar dev:env:set [env]

Updates the config to match values set in ~/.n98-magerun.yaml. See https://github.com/ProjectEight/magerun for detailed instructions

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
		 * If no environment passed, ask user for one
		 */
//		$this->disableObservers();
		$environment = $input->getArgument('env');
		if ($environment === null) {
			$this->writeSection($output, 'Available environments');
			$environmentList = $this->getEnvironmentsList();
			$question = array();
			foreach ($environmentList as $key => $environment) {
				$question[] = '<comment>' . str_pad('[' . ($key + 1) . ']', 4, ' ', STR_PAD_RIGHT) . '</comment> ' .
				              str_pad($environment, 40, ' ', STR_PAD_RIGHT) . "\n";
			}
			$question[] = '<question>Please select an environment to update: </question>';

			/** @var DialogHelper $dialog */
			$dialog = $this->getHelper('dialog');
			$environment = $dialog->askAndValidate($output, $question, function ($typeInput) use ($environmentList) {
				$typeInputs = array($typeInput);

				$returnCode = null;
				foreach ($typeInputs as $typeInput) {
					if (!isset($environmentList[$typeInput - 1])) {
						throw new InvalidArgumentException('Environment not found');
					}

					$returnCode = $environmentList[$typeInput - 1];
				}

				return $returnCode;
			});
		}

		try {

			/*
			 * Get config settings for environment
			 */

			$this->writeSection($output, 'Updating config');

			$configSettings = $this->getEnvironmentConfig($environment);

			// ensure that n98-magerun doesn't stop after first command
			$this->getApplication()->setAutoExit(false);

			foreach ( $configSettings as $configScopeCode => $configScopes) {

				foreach ( $configScopes as $configScopeId => $configOptions ) {

					/*
					 * Use existing config:set command
					 */

					foreach ( $configOptions as $configPath => $configValue ) {

						$commandOptions = " --scope {$configScopeCode} ";
						$commandOptions .= " --scope-id {$configScopeId} ";
						$commandOptions .= $configPath . " ";
						$commandOptions .= '"' . $configValue . '"';

						$input = new StringInput("config:set {$commandOptions}");
			//			$input = new StringInput('config:set --scope websites --scope-id 2 carriers/flatrate/active 0');

						// with output
						$this->getApplication()->run($input, $output);
					}
				}
			}

			// Clear config cache
//			$input = new StringInput('cache:clean config');

			// Execute command without output
//			$this->getApplication()->run($input, new NullOutput());

			// reactivate auto-exit
			$this->getApplication()->setAutoExit(true);

		} catch (Exception $e) {
			$output->writeln('<error>' . $e->getMessage() . '</error>');
		}
	}

	/**
	 * @param OutputInterface $output
	 */
	protected function getEnvironmentsList()
	{
		$environmentNames = [];
		$config = $this->getCommandConfig();
		if (isset($config['environments']) && is_array($config['environments'])) {
			foreach ($config['environments'] as $environmentName => $environmentOptions) {
				$environmentNames[] = $environmentName;
			}
		}

		return $environmentNames;
	}

	/**
	 * @param string $environment
	 *
	 * @return array
	 */
	protected function getEnvironmentSettings($environment)
	{
		$settings = [];
		$config = $this->getCommandConfig();
		if (isset($config['environments'][$environment]) && is_array($config['environments'][$environment])) {
			foreach ($config['environments'][$environment] as $settingKey => $settingValue) {

			}
		}

		return $settings;
	}

	/**
	 * @param string $environment
	 *
	 * @return array
	 */
	protected function getEnvironmentConfig($environment)
	{
		$configSettings = [];
		$config = $this->getCommandConfig();
		if (isset($config['environments'][$environment]['config']) && is_array($config['environments'][$environment]['config'])) {
			return $config['environments'][$environment]['config'];
//			foreach ($config['environments'][$environment]['config'] as $configPath => $configValue) {
//				$configSettings[$configPath] = $configValue;
//			}
		}

		return $configSettings;
	}

}
