<?php

declare(strict_types=1);

use Contao\CoreBundle\DataContainer\PaletteManipulator;

$GLOBALS['TL_DCA']['tl_settings']['fields']['cryptmailto_text'] = [
    'inputType' => 'text',
    'eval' => ['tl_class' => 'long'],
];

PaletteManipulator::create()
    ->addLegend('cryptmailto_legend', 'uploads_legend', PaletteManipulator::POSITION_AFTER)
    ->addField('cryptmailto_text', 'cryptmailto_legend', PaletteManipulator::POSITION_APPEND)
    ->applyToPalette('default', 'tl_settings')
;
