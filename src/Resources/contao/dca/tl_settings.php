<?php

use Contao\CoreBundle\DataContainer\PaletteManipulator;

$GLOBALS['TL_DCA']['tl_settings']['subpalettes']['ds_analytics_enable_pixel']  = 'ds_analytics_pixel_id,ds_analytics_pixel_token';
$GLOBALS['TL_DCA']['tl_settings']['palettes']['__selector__'][] = 'ds_analytics_enable_pixel';

PaletteManipulator::create()
    ->addLegend('ds_analytics_config_legend', 'backend_legend', PaletteManipulator::POSITION_AFTER)
    ->addField('ds_analytics_enable_pixel','ds_analytics_config_legend', PaletteManipulator::POSITION_PREPEND)
    ->addField('ds_analytics_enable_google','ds_analytics_config_legend',PaletteManipulator::POSITION_PREPEND)
    ->addField('ds_analytics_checkout_page','ds_analytics_config_legend',PaletteManipulator::POSITION_PREPEND)
    ->addField('ds_analytics_complete_page','ds_analytics_config_legend',PaletteManipulator::POSITION_PREPEND)
    ->applyToPalette('default', 'tl_settings');


// Fields
$GLOBALS['TL_DCA']['tl_settings']['fields']['ds_analytics_enable_pixel'] = [
    'exclude'   => true,
    'inputType' => 'checkbox',
    'eval'      => ['submitOnChange'=>true, 'tl_class'=> 'clr']
];

$GLOBALS['TL_DCA']['tl_settings']['fields']['ds_analytics_enable_google'] = [
    'exclude'   => true,
    'inputType' => 'checkbox',
    'eval'      => ['tl_class'=> 'clr']
];

$GLOBALS['TL_DCA']['tl_settings']['fields']['ds_analytics_pixel_id'] = [
    'exclude'   => true,
    'inputType' => 'text',
    'eval'      => ['mandatory'=>true]
];

$GLOBALS['TL_DCA']['tl_settings']['fields']['ds_analytics_pixel_token'] = [
    'exclude'   => true,
    'inputType' => 'text',
    'eval'      => ['mandatory'=>true]
];

$GLOBALS['TL_DCA']['tl_settings']['fields']['ds_analytics_complete_page'] = array
(
    'inputType'   => 'pageTree',
    'foreignKey'  => 'tl_page.title',
    'eval'        => ['fieldType'=>'radio', 'mandatory'=>true],
    'explanation' => 'jumpTo',
    'relation'    => ['type'=>'hasOne', 'load'=>'lazy'],
);

$GLOBALS['TL_DCA']['tl_settings']['fields']['ds_analytics_checkout_page'] = array
(
    'inputType'   => 'pageTree',
    'foreignKey'  => 'tl_page.title',
    'eval'        => ['fieldType'=>'radio', 'mandatory'=>true],
    'explanation' => 'jumpTo',
    'relation'    => ['type'=>'hasOne', 'load'=>'lazy'],
);