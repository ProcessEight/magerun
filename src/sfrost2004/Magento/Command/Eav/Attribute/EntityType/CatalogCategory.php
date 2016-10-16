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

class CatalogCategory extends AbstractEntityType implements EntityType
{
    /**
     * Gets key legend for catalog category attribute
     * Note that catalog_product and catalog_category entities share the same setup resource model,
     * so some of these fields are only relevant to catalog_product
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
            'is_wysiwyg_enabled'            => 'wysiwyg_enabled',
            'is_visible_in_advanced_search' => 'visible_in_advanced_search',
            'is_filterable_in_search'       => 'filterable_in_search',
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
	 * Note that catalog_product and catalog_category entities share the same setup resource model,
	 * so some of these fields are only relevant to catalog_product
	 *
	 * @return array
	 */
	protected function _getDefaultValues()
	{
		$data = parent::_getDefaultValues();

		// Catalog product default values
		$data = array_merge($data, array(
			'is_global'                     => \Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
			'is_visible'                    => 1,
			'is_visible_on_front'           => 0,
			'is_wysiwyg_enabled'            => 0,
			'is_html_allowed_on_front'      => 0,
			'position'                      => 0,
			'group'                         => 'General Information',
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

/*
 * startSetup() and endSetup() are intentionally omitted
 */

/* @var \$setup Mage_Catalog_Model_Resource_Setup */
\$setup = new Mage_Catalog_Model_Resource_Setup('core_setup');

/*
 * 'group' indicates the tab the attribute will be added to. If the tab doesn't exist, Magento will create it.
 */
\$data = $arrayCode;
\$setup->addAttribute(Mage_Catalog_Model_Category::ENTITY, '" . $this->attribute . "', \$data);
            ";

        return $script;
    }
}
