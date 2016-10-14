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

/**
 * Class AbstractEntityType
 *
 * @package N98\Magento\Command\Developer\Setup\Script\Attribute\EntityType
 */
abstract class AbstractEntityType implements EntityType
{
    /**
     * @var \Varien_Db_Adapter_Interface
     */
    protected $readConnection;

    /**
     * @var \Mage_Eav_Model_Entity_Attribute
     */
    protected $attribute;

    /**
     * @var string
     */
    protected $entityType;

    /**
     * @var array
     */
    protected $warnings = array();

    /**
     * @param string $attributeCode
     */
    public function __construct($attributeCode)
    {
        $this->attribute = $attributeCode;
    }

    /**
     * @param $connection
     */
    public function setReadConnection($connection)
    {
        $this->readConnection = $connection;
    }

    /**
     * @param array $warnings
     */
    public function setWarnings($warnings)
    {
        $this->warnings = $warnings;
    }

    /**
     * @return array
     */
    public function getWarnings()
    {
        return $this->warnings;
    }

	/**
	 * Get default attribute values
	 *
	 * @return array
	 */
	protected function _getDefaultValues()
	{
		return array(
			'backend_model'   => NULL,
			'backend_type'    => 'varchar',
			'backend_table'   => NULL,
			'frontend_model'  => NULL,
			'frontend_input'  => 'text',
			'frontend_label'  => NULL,
			'frontend_class'  => NULL,
			'source_model'    => NULL,
			'is_required'     => 0,
			'is_user_defined' => 1,
			'default_value'   => NULL,
			'is_unique'       => 0,
			'note'            => NULL,
			'is_global'       => 1,
			'label'           => ucwords(str_replace('_', ' ', $this->attribute)),
		);
	}
}
