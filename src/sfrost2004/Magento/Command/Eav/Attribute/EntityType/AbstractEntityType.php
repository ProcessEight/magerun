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
     * Field of attribute (e.g. 'is_global')
     *
     * @var string
     */
    protected $_field;

    /**
     * Value of attribute field. Not to be confused with the attribute value itself.
     *
     * @var string
     */
    protected $_value;

    /**
     * @var array
     */
    protected $warnings = array();

	/**
	 * @param string $attributeCode
	 * @param string $field
	 * @param string $value
	 */
    public function __construct($attributeCode, $field, $value)
    {
        $this->attribute = $attributeCode;
	    $this->setField($field);
	    $this->setValue($value);
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
	 * @return string
	 */
	public function getAttribute() {
		return $this->attribute;
	}

	/**
	 * @param string $attribute
	 *
	 * @return $this
	 */
	public function setAttribute( $attribute ) {
		$this->attribute = $attribute;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getValue() {
		return $this->_value;
	}

	/**
	 * @param string $value
	 *
	 * @return $this
	 */
	public function setValue( $value ) {
		$this->_value = $value;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getField() {
		return $this->_field;
	}

	/**
	 * @param string $field
	 *
	 * @return $this
	 */
	public function setField( $field ) {
		$this->_field = $field;

		return $this;
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
