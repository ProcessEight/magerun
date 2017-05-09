<?php
/**
 * ProjectEight
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future. If you wish to customize this module for your
 * needs please contact ProjectEight for more information.
 *
 * @category    ProjectEight
 * @package     ProjectEight
 * @copyright   Copyright (c) 2016 ProjectEight
 * @author      Simon Frost, ProjectEight
 *
 */

namespace ProjectEight\Magento\Command\Eav\Attribute\EntityType;

interface EntityType
{
    /**
     * @param string $attributeCode
     */
    public function __construct($attributeCode, $frontendInput);

    /**
     * @param $connection
     * @return void
     */
    public function setReadConnection($connection);

    /**
     * @return array
     */
    public function getWarnings();

    /**
     * @return string
     */
    public function generateCode();
}
