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

namespace sfrost2004\Magento\Command\Developer\ResourceScript\Type;

use RuntimeException;

class Factory
{
    /**
     * @param string $type
     * @param string $id
     *
     * @return mixed
     */
    public static function create($type, $id)
    {
        $words = explode('_', strtolower($type));
        $class = __NAMESPACE__ . '\\';
        foreach ($words as $word) {
            $class .= ucfirst(trim($word));
        }

        if (!class_exists($class)) {
            throw new RuntimeException(
            	'No script generator for this type available (The script is looking for ' . $class . '). '
	                . 'Acceptable values are ' . trim(implode(', ', self::acceptableTypes()), ', ')
            );
        }

        return new $class($type, $id);
    }

    /**
     * @return array
     */
    public static function acceptableTypes()
    {
        return [
            'cms_block' => 'cms/block',
        ];
    }
}
