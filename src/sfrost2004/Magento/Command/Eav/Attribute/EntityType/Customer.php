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

class Customer extends AbstractEntityType implements EntityType
{
    /**
     * Gets key legend for catalog product attribute
     *
     * @return array
     */
    protected function _getKeyMapping()
    {
        return array(
            'frontend_input_renderer'       => 'input_renderer',
            'is_global'                     => 'global',
            'is_visible'                    => 'visible',
            'is_searchable'                 => 'searchable',
            'is_filterable'                 => 'filterable',
            'is_comparable'                 => 'comparable',
            'is_visible_on_front'           => 'visible_on_front',
            'is_wysiwyg_enabled'            => 'wysiwyg_enabled',
            'is_visible_in_advanced_search' => 'visible_in_advanced_search',
            'is_filterable_in_search'       => 'filterable_in_search',
            'is_used_for_promo_rules'       => 'used_for_promo_rules',
            'backend_model'                 => 'backend',
            'backend_type'                  => 'type',
            'backend_table'                 => 'table',
            'frontend_model'                => 'frontend',
            'frontend_input'                => 'input',
            'frontend_label'                => 'label',
            'frontend_class'                => 'frontend_class',
            'source_model'                  => 'source',
            'is_required'                   => 'required',
            'is_user_defined'               => 'user_defined',
            'default_value'                 => 'default',
            'is_unique'                     => 'unique',
            'note'                          => 'note',
            'group'                         => '<Label of tab the attribute appears in>',
            'position'                      => '999',
        );
    }

	/**
	 * Get default attribute values
	 *
	 * @return array
	 */
	protected function _getDefaultValues()
	{
		$data = parent::_getDefaultValues();

		// Customer default values
		$data = array_merge($data, array(
			'is_visible'                => 1,
			'is_system'                 => 0,
			'input_filter'              => null,
			'multiline_count'           => 0,
			'validate_rules'            => null,
			'data_model'                => null,
			'sort_order'                => 0,
		));
		return $data;
	}

    /**
     * @return string
     */
    public function generateCode()
    {
        // get a map of 'real' attribute properties to properties used in setup resource array
        $realToSetupKeyLegend = $this->_getKeyMapping();

        // swap keys from above
        $data = $this->_getDefaultValues();
        $keysLegend = array_keys($realToSetupKeyLegend);
        $newData = array();

        foreach ($data as $key => $value) {
            if (in_array($key, $keysLegend)) {
                $key = $realToSetupKeyLegend[$key];
            }
            $newData[$key] = $value;
        }

        // chuck a few warnings out there for things that were a little murky
        if ($newData['attribute_model']) {
            $this->warnings[] = '<warning>WARNING, value detected in attribute_model. We\'ve never seen a value ' .
                'there before and this script doesn\'t handle it.  Caution, etc. </warning>';
        }

        if ($newData['is_used_for_price_rules']) {
            $this->warnings[] = '<error>WARNING, non false value detected in is_used_for_price_rules. ' .
                'The setup resource migration scripts may not support this (per 1.7.0.1)</error>';
        }

        //get text for script
        $arrayCode = var_export($newData, true);

        //generate script using simple string concatenation, making
        //a single tear fall down the cheek of a CS professor
        $script = "<?php
        
/* @var \$setup Mage_Customer_Model_Entity_Setup */
\$setup = new Mage_Customer_Model_Entity_Setup('core_setup');

\$data = $arrayCode;
\$setup->addAttribute('customer', '" . $this->attribute . "', \$data);
            ";

	    $customerFormsScript = "
/*
 *  Note you only need to worry about form codes if the customer attribute is_system == 0 and is_visible == 1
 *
 *  mysql> select distinct(form_code) from customer_form_attribute;
 *  +----------------------------+
 *  | form_code                  |
 *  +----------------------------+
 *  | adminhtml_checkout         |
 *  | adminhtml_customer         |
 *  | adminhtml_customer_address |
 *  | checkout_register          |
 *  | customer_account_create    |
 *  | customer_account_edit      |
 *  | customer_address_edit      |
 *  | customer_register_address  |
 *  +----------------------------+
 *  8 rows in set (0.00 sec)
 */

\$customerAttribute = Mage::getSingleton('eav/config')->getAttribute('customer', '" . $this->attribute . "');
\$customerAttribute->setData('used_in_forms', array(
	'customer_account_create','customer_account_edit'
));
\$customerAttribute->save();
";

	    $script .= $customerFormsScript;

        return $script;
    }
}
