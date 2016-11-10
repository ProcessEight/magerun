<?php
/**
 * sfrost2004
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future. If you wish to customize this module for your
 * needs please contact Zone8 for more information.
 *
 * @category    sfrost2004
 * @package     sfrost2004
 * @copyright   Copyright (c) 2016 sfrost2004
 * @author      Simon Frost, sfrost2004
 *
 */

namespace sfrost2004\Magento\Command\Eav\Attribute\EntityType;

interface EntityType
{
    /**
     * @param string $attributeCode
     */
    public function __construct($attributeCode, $field, $value);

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
