<?php

namespace ProjectEight\Magento\Command\System\Report\Patches;

use N98\Magento\Command\CommandAware;
use ProjectEight\Magento\Command\System\Report\Result;
use ProjectEight\Magento\Command\System\Report\ResultCollection;
use ProjectEight\Magento\Command\System\Report\SimpleReport;
use Symfony\Component\Console\Command\Command;
use ProjectEight\Magento\Command\System\ReportCommand;

/**
 * Class AppliedReport
 *
 * @package ProjectEight\Magento\Command\System\Report\Patches
 */
class AppliedReport implements SimpleReport, CommandAware
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

        $appliedPatchesList = 'app' . DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'applied.patches.list';
        $magentoRoot = $this->reportCommand->getApplication()->getMagentoRootFolder();
        $appliedPatchesListPath = $magentoRoot . DIRECTORY_SEPARATOR . $appliedPatchesList;

        $ioAdapter = new \Varien_Io_File();
        if (!$ioAdapter->fileExists($appliedPatchesListPath)) {
            $result->setMessage("<info>File <comment>{$appliedPatchesList}</comment> not found.</info>");
            return;
        }
        $ioAdapter->open(['path' => $ioAdapter->dirname($appliedPatchesListPath)]);
        $ioAdapter->streamOpen($appliedPatchesListPath, 'r');

        $appliedPatches = [];
        while ($buffer = $ioAdapter->streamRead()) {
            if(stristr($buffer,'|')){
                list($date, $patch, $magentoVersion, $patchVersion) = array_map('trim', explode('|', $buffer));
                $appliedPatches[] = $patch . " " . $patchVersion;
            }
        }
        $ioAdapter->streamClose();

        $appliedPatchesString = "<comment>";
        $appliedPatchesString .= implode("</comment>\n<comment>", array_reverse($appliedPatches));
        $appliedPatchesString .= "</comment>\n";

        $result->setMessage("<info>Patches applied: \n" . $appliedPatchesString . "</info>");
    }

    /**
     * @param Command $command
     */
    public function setCommand(Command $command)
    {
        $this->reportCommand = $command;
    }
}
