<?php

namespace sfrost2004\Magento\Command\System\Admin\Login;

use LogicException;
use N98\Magento\Command\AbstractMagentoCommand;
use N98\Magento\Command\CommandAware;
use N98\Magento\Command\CommandConfigAware;
use N98\Magento\Command\System\Check\Result;
use N98\Magento\Command\System\Check\ResultCollection;
use N98\Util\Console\Helper\Table\Renderer\RendererFactory;
use N98\Util\Unicode\Charset;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class CheckCommand
 *
 * @package sfrost2004\Magento\Command\System\Admin\Login
 */
class CheckCommand extends AbstractMagentoCommand
{
    /**
     * Command config
     *
     * @var array
     */
    private $config;

    protected function configure()
    {
        $this
            ->setName('admin:login:check')
            ->setDescription('Diagnoses common issues which prevent logging in to the admin');

        $help = <<<HELP
This command will check the following common issues which prevent logging in to the admin:

- Check what type of session management is configured in app/etc/local.xml. If it's files, just delete the contents of the var/session directory. If it's db, then truncate the core_session table. If you're using something more complicated like Redis or memcached, make sure it's actually installed and configured correctly, especially seeing as you say you've moved servers.
- Incorrect permissions on var/session, meaning the server can't write to the directory and sessions can't be started
- Because of the above, Magento can't write to var/session, so it may be writing to the servers' temp directory (e.g. /tmp/magento/var/session/)
- Make sure your browser isn't blocking cookies
- Try in private browsing mode, or a different browser
- Check your cookies. Make sure there are no duplicate adminhtml cookies
- Make sure there is no PHPSESSID cookie - this is an indication that the session is being created too early, using the default PHP session cookie name, rather than Magento's choice of name for admin sessions (adminhtml). This could indicate an extension may trying to start a session too early.
- Have you installed any new extensions? Try setting disable_local_modules to true in app/etc/local.xml. If you can now login, try switch it back to false, disable all your community and local modules and then try enabling them one by one.
- Are you hosting the site locally? Set your hostname to something that includes periods (.). Webkit browsers have problems setting cookies to domains without any periods (e.g. http://localhost/).
- Make sure you have the correct cookie domain set, especially if you have multiple websites. Use this SQL snippet to check what's configured:
- Mismatch between server time and local computer time, meaning cookies are instantly unset
- Someone has hacked the class Mage_Core_Model_Session_Abstract_Varien. Download a copy of the Magento version your website is running from the Magento website and compare the class in there to your class.
- Not enough disk space on the server, preventing session files being written
- A developer has removed the formkey form element (unlikely), preventing Magento from processing the form. Look in the HTML source code of the login page for a form field called formkey
- Use n98-magerun's sys:check command to find any other issues which may be causing this behaviour
- There is a login issue in Google Chrome. Set Use HTTP only to No in the Session Cookie Management section of System, Configuration, Web. Don't do this on production servers as it is a security risk. Use this SQL snippet to update it: UPDATE core_config_data SET value = '0' WHERE path = 'web/cookie/cookie_httponly';

http://magento.stackexchange.com/questions/121457/magento-admin-login-refreshes-for-correct-credentials
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
        $this->detectMagento($output);
        if (!$this->initMagento()) {
            return;
        }

        $this->config = $this->getCommandConfig();

//        $results = new ResultCollection();
//
//        foreach ($this->config['checks'] as $checkGroup => $checkGroupClasses) {
//            $results->setResultGroup($checkGroup);
//            foreach ($checkGroupClasses as $checkGroupClass) {
//                $this->_invokeCheckClass($results, $checkGroupClass);
//            }
//        }
//
//        if ($input->getOption('format')) {
//            $this->_printTable($input, $output, $results);
//        } else {
//            $this->_printResults($output, $results);
//        }
    }

    /**
     * @param ResultCollection $results
     * @param string $checkGroupClass name
     */
    protected function _invokeCheckClass(ResultCollection $results, $checkGroupClass)
    {
        $check = $this->_createCheck($checkGroupClass);

        switch (true) {
            case $check instanceof Check\SimpleCheck:
                $check->check($results);
                break;

            case $check instanceof Check\StoreCheck:
                $this->checkStores($results, $checkGroupClass, $check);
                break;

            case $check instanceof Check\WebsiteCheck:
                $this->checkWebsites($results, $checkGroupClass, $check);
                break;

            default:
                throw new LogicException(
                    sprintf('Unhandled check-class "%s"', $checkGroupClass)
                );
        }
    }

    /**
     * @param OutputInterface $output
     * @param ResultCollection $results
     */
    protected function _printResults(OutputInterface $output, ResultCollection $results)
    {
        $lastResultGroup = null;
        foreach ($results as $result) {
            if ($result->getResultGroup() != $lastResultGroup) {
                $this->writeSection($output, str_pad(strtoupper($result->getResultGroup()), 60, ' ', STR_PAD_BOTH));
            }
            if ($result->getMessage()) {
                switch ($result->getStatus()) {
                    case Result::STATUS_WARNING:
                    case Result::STATUS_ERROR:
                        $output->write('<error>' . Charset::convertInteger(Charset::UNICODE_CROSS_CHAR) . '</error> ');
                        break;

                    case Result::STATUS_OK:
                    default:
                        $output->write(
                            '<info>' . Charset::convertInteger(Charset::UNICODE_CHECKMARK_CHAR) . '</info> '
                        );
                        break;
                }
                $output->writeln($result->getMessage());
            }

            $lastResultGroup = $result->getResultGroup();
        }
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param ResultCollection $results
     */
    protected function _printTable(InputInterface $input, OutputInterface $output, ResultCollection $results)
    {
        $table = array();
        foreach ($results as $result) {
            /* @var $result Result */
            $table[] = array(
                $result->getResultGroup(),
                strip_tags($result->getMessage()),
                $result->getStatus(),
            );
        }

        $this->getHelper('table')
            ->setHeaders(array('Group', 'Message', 'Result'))
            ->renderByFormat($output, $table, $input->getOption('format'));
    }

    /**
     * @param string $checkGroupClass
     *
     * @return object
     */
    private function _createCheck($checkGroupClass)
    {
        $check = new $checkGroupClass();

        if ($check instanceof CommandAware) {
            $check->setCommand($this);
        }
        if ($check instanceof CommandConfigAware) {
            $check->setCommandConfig($this->config);

            return $check;
        }

        return $check;
    }

    /**
     * @param ResultCollection $results
     * @param string $context
     * @param string $checkGroupClass
     */
    private function _markCheckWarning(ResultCollection $results, $context, $checkGroupClass)
    {
        $result = $results->createResult();
        $result->setMessage(
            '<error>No ' . $context . ' configured to run store check:</error> <comment>' . basename($checkGroupClass) .
            '</comment>'
        );
        $result->setStatus($result::STATUS_WARNING);
        $results->addResult($result);
    }

    /**
     * @param ResultCollection $results
     * @param string $checkGroupClass name
     * @param Check\StoreCheck $check
     */
    private function checkStores(ResultCollection $results, $checkGroupClass, Check\StoreCheck $check)
    {
        if (!$stores = \Mage::app()->getStores()) {
            $this->_markCheckWarning($results, 'stores', $checkGroupClass);
        }
        foreach ($stores as $store) {
            $check->check($results, $store);
        }
    }

    /**
     * @param ResultCollection $results
     * @param string $checkGroupClass name
     * @param Check\WebsiteCheck $check
     */
    private function checkWebsites(ResultCollection $results, $checkGroupClass, Check\WebsiteCheck $check)
    {
        if (!$websites = \Mage::app()->getWebsites()) {
            $this->_markCheckWarning($results, 'websites', $checkGroupClass);
        }
        foreach ($websites as $website) {
            $check->check($results, $website);
        }
    }
}
