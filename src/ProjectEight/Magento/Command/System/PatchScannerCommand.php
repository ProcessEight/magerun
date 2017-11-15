<?php

namespace ProjectEight\Magento\Command\System;

use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PatchScannerCommand extends AbstractMagentoCommand
{
    /**
     * @var string
     */
    protected $patchFile;

    /**
     * All the hunks of each file
     *
     * @var array
     */
    protected $files;

    /**
     * Configure command
     */
    protected function configure()
    {
        $this
            ->setName('sys:patch-scanner')
            ->addArgument(
                'patch-file-path',
                InputArgument::REQUIRED,
                'Path to patch file'
            )->setDescription('Scans a Magento environment to detect if the specified SUPEE patch has been installed.')
        ;
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $patchFilePath = $input->getArgument('patch-file-path');

        $this->readPatchFile($patchFilePath);

        $list = $this->getFileList();

        $messages[] = "Checking " . count($list) . " files...";

        $this->parseFiles();

        $this->checkFiles();

        $results = $this->checkFiles($list);

        $messages[] = implode("\n", $results);

        $output->writeln('<info>' . implode("\n", $messages) . '</info>');
    }

    /**
     * Read patch file into string
     *
     * @param string $patchFilePath
     *
     * @return void
     */
    protected function readPatchFile($patchFilePath)
    {
        $this->patchFile = file_get_contents($patchFilePath);
    }

    /**
     * Get an array of all the files in the patch
     *
     * @return array $patchFiles
     */
    protected function getFileList()
    {
        $patchFiles = [];
        $lines = explode("\n", $this->patchFile);

        foreach ($lines as $line) {
            if(substr($line, 0, 3) === "+++") {
                $patchFiles[] = $line;
            }
        }

        return $patchFiles;
    }

    /**
     * Check if files have had patch applied
     *
     * @return array $results
     */
    protected function checkFiles()
    {
        $results = [];
        foreach ($this->files as $file) {
            // Extract hunk
            $lines = explode("\n", $file);
            $sourceFile = explode(" ", $lines[0]);
            $hunkId = $lines[4];

            $lines = array_slice($lines, 5);
            foreach ($lines as $line) {
                switch($line[0]) {
                    case ' ': $results[$sourceFile[0]][$hunkId]['context'][] = $line; break;
                    case '+': $results[$sourceFile[0]][$hunkId]['additions'][] = $line; break;
                    case '-': $results[$sourceFile[0]][$hunkId]['deletions'][] = $line; break;
                }
            }

            // Read in source file
            
            // Search source file for hunk additions/deletions
        }

        return $results;
    }

    /**
     * Parse the files in the patch file
     *
     * @return void
     */
    protected function parseFiles()
    {
        $parts = explode("__PATCHFILE_FOLLOWS__", $this->patchFile);
        $this->files = explode("diff --git ", $parts[3]);
    }
}
