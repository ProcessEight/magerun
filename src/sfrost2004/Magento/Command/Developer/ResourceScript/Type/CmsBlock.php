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

class CmsBlock extends AbstractType implements Type
{
    /**
     * @return string
     */
    public function generateCode()
    {
        // Load entity
        $model = $this->getModel();

        //get text for script
        $code = var_export($model->getData(), true);

        //generate script using simple string concatenation, making
        //a single tear fall down the cheek of a CS professor
        $script = "<?php

/* @var \$installer Mage_Core_Model_Resource_Setup */
\$installer = \$this;

\$installer->startSetup();

    \$storeId = Mage::app()->getStore()->getId();

	/** @var Mage_Cms_Model_Block \$block */
	\$block = Mage::getModel('cms/block');

	\$data = {$code};

    \$block->setStoreId(\$storeId);
	\$block->setData(\$data);
	\$block->save();

\$installer->endSetup();
";

        return $script;
    }

}
