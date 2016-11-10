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

use RuntimeException;

class Factory
{
    /**
     * @param string $entityType
     * @param string $attributeCode
     *
     * @return mixed
     */
    public static function create($entityType, $attributeCode, $field = null, $value = null)
    {
        $words = explode('_', strtolower($entityType));
        $class = __NAMESPACE__ . '\\';
        foreach ($words as $word) {
            $class .= ucfirst(trim($word));
        }

        if (!class_exists($class)) {
            throw new RuntimeException(
            	'No script generator for this entity type available (The script is looking for ' . $class . ')'
            );
        }

        return new $class($attributeCode, $field, $value);
    }
}
