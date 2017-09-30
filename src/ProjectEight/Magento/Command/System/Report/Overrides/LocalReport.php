<?php

namespace ProjectEight\Magento\Command\System\Report\Overrides;

use N98\Magento\Command\CommandAware;
use ProjectEight\Magento\Command\System\Report\Result;
use ProjectEight\Magento\Command\System\Report\ResultCollection;
use ProjectEight\Magento\Command\System\Report\SimpleReport;
use Symfony\Component\Console\Command\Command;
use ProjectEight\Magento\Command\System\ReportCommand;

/**
 * Class AppliedReport
 *
 * @package ProjectEight\Magento\Command\System\Report\Overrides
 */
class LocalReport implements SimpleReport, CommandAware
{
    /**
     * @var ReportCommand
     */
    protected $reportCommand;

    /**
     * @param ResultCollection $results
     *
     * @return void
     */
    public function report(ResultCollection $results)
    {
        $result = $results->createResult();
        $result->setStatus(Result::STATUS_INFO);

        list($localCodepoolMageDirectory, $localCodepoolMageDirectoryPath) = $this->getLocalCodepoolMageDirectoryAbsolutePath();

        $ioAdapter = new \Varien_Io_File();
        $ioAdapter->setAllowCreateFolders(false);
        if (!$ioAdapter->fileExists($localCodepoolMageDirectoryPath, false)) {
            $result->setMessage("<info>File <comment>{$localCodepoolMageDirectory}</comment> not found.</info>");

            return;
        }
        $ioAdapter->open(['path' => $ioAdapter->dirname($localCodepoolMageDirectoryPath)]);

        $directoryIterator = new \RecursiveDirectoryIterator(
            $localCodepoolMageDirectoryPath,
            \FilesystemIterator::KEY_AS_PATHNAME |
            \FilesystemIterator::CURRENT_AS_FILEINFO |
            \FilesystemIterator::SKIP_DOTS
        );
        $iteratorIterator  = new \RecursiveIteratorIterator($directoryIterator);
        $regexIterator     = new \RegexIterator(
            $iteratorIterator,
            '/^.+\.php$/i',
            \RecursiveRegexIterator::GET_MATCH
        );
        $localOverrides    = array_keys(iterator_to_array($regexIterator));

        $ioAdapter->streamClose();

        $appliedPatchesString = "<comment>";
        $appliedPatchesString .= implode("</comment>\n<comment>", $localOverrides);
        $appliedPatchesString .= "</comment>\n";

        $result->setMessage("<info>Files overridden in <comment>app/code/local/Mage</comment>: \n" . $appliedPatchesString . "</info>");
    }

    /**
     * @param Command $command
     */
    public function setCommand(Command $command)
    {
        $this->reportCommand = $command;
    }

    /**
     * @return array
     */
    protected function getLocalCodepoolMageDirectoryAbsolutePath() : array
    {
        $localCodepoolMageDirectory     = 'app' .
                                          DIRECTORY_SEPARATOR . 'code' .
                                          DIRECTORY_SEPARATOR . 'local' .
                                          DIRECTORY_SEPARATOR . 'Mage' .
                                          DIRECTORY_SEPARATOR;
        $magentoRoot                    = $this->reportCommand->getApplication()->getMagentoRootFolder();
        $localCodepoolMageDirectoryPath = $magentoRoot . DIRECTORY_SEPARATOR . $localCodepoolMageDirectory;

        return [$localCodepoolMageDirectory, $localCodepoolMageDirectoryPath];
    }
}
