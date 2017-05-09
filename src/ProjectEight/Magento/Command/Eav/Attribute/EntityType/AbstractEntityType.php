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
    protected $frontendInput;

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
	 * @param string $frontendInput
	 */
    public function __construct($attributeCode, $frontendInput)
    {
        $this->attribute = $attributeCode;
        $this->frontendInput = $frontendInput;
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
