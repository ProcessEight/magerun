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

class CatalogProduct extends AbstractEntityType implements EntityType
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
            'group'                         => 'group',
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

		// Catalog product default values
		$data = array_merge($data, array(
			'frontend_input_renderer'       => NULL,
			'is_global'                     => \Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
			'is_visible'                    => 1,
			'is_searchable'                 => 0,
			'is_filterable'                 => 0,
			'is_comparable'                 => 0,
			'is_visible_on_front'           => 0,
			'is_wysiwyg_enabled'            => 0,
			'is_html_allowed_on_front'      => 0,
			'is_visible_in_advanced_search' => 0,
			'is_filterable_in_search'       => 0,
			'used_in_product_listing'       => 0,
			'used_for_sort_by'              => 0,
			'apply_to'                      => NULL,
			'position'                      => 999,
			'is_configurable'               => 0,
			'is_used_for_promo_rules'       => 0,
			'group'                         => 'General',
		));

		// Merge in specific values for different frontend_input types
		$data = array_merge($data, $this->_getFrontendInputSpecificDefaultValues());

		return $data;
	}

	/**
	 * Return default values for different frontend_input types
	 *
	 * @return array
	 */
	protected function _getFrontendInputSpecificDefaultValues()
	{
		switch ($this->frontendInput) {
			case 'multiselect':
				$data = $this->_getMultiselectDefaultValues();
				break;

			default:
				$data = [];
				break;
		}

		return $data;
	}

	/**
	 * Default values for attributes with frontend_input of multiselect
	 *
	 * @return array
	 */
	protected function _getMultiselectDefaultValues()
	{
		$data = [
			'backend'   => 'eav/entity_attribute_backend_array',
		    'type'      => 'text',
		    'input'     => 'multiselect',
//		    'default'   => '<Attribute Option ID>',
			'option' =>
				array (
					'values' =>
						array (
							'<Sort Order>' => '<Admin Store Label>',
						),
				),

		];

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

/*
 * startSetup() and endSetup() are intentionally omitted
 */
        
/* @var \$setup Mage_Catalog_Model_Resource_Setup */
\$setup = new Mage_Catalog_Model_Resource_Setup('core_setup');

/* 
 *  Note that apply_to can accept a string of product types, e.g. 'simple,configurable,grouped' 
 *  or omit it to apply to all product types
 */
\$data = $arrayCode;
\$setup->addAttribute('catalog_product', '" . $this->attribute . "', \$data);
            ";

		$labelsScript = "
/*
 * Add different labels for multi-store setups
 * Labels should be added in [store_id => label, ...] array format
 */
// \$attribute = Mage::getModel('eav/entity_attribute')->loadByCode('catalog_product', '" . $this->attribute . "');
// \$attribute->setStoreLabels(array (
//      '<store_id>' => 'Label',
// ));
// \$attribute->save();
";

        $script .= $labelsScript;

        return $script;
    }
}
