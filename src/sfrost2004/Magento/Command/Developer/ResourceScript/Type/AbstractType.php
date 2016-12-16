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

/**
 * Class AbstractType
 *
 * @package N98\Magento\Command\Developer\Setup\Script\Attribute\EntityType
 */
abstract class AbstractType implements Type
{
    /**
     * @var \Varien_Db_Adapter_Interface
     */
    protected $readConnection;

    /**
     * @var int
     */
    protected $id;

    /**
     * @var string
     */
    protected $type;

    /**
     * @var array
     */
    protected $warnings = array();

	/**
	 * @param string $type
	 * @param string $id
	 */
    public function __construct($type, $id)
    {
        $this->id = $id;
		$this->type = $type;
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
	 * @throws RuntimeException
	 * @return \Mage_Core_Model_Abstract
	 */
	protected function getModel()
	{
		$classGroupAlias = $this->_getClassGroupAlias();
		if(!$classGroupAlias) {
			throw new RuntimeException(
				'Class group alias not defined for ' . $this->type. ''
			);
		}
		return \Mage::getModel($classGroupAlias)->load($this->id);
	}

	protected function _getClassGroupAlias()
	{
		$types = Factory::acceptableTypes();

		return isset($types[$this->type]) ? $types[$this->type] : false;
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
			'label'           => ucwords(str_replace('_', ' ', $this->id)),
		);
	}
}
