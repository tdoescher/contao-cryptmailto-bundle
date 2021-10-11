<?php

namespace tdoescher\IfCookieBundle\EventListener\DataContainer;

use Contao\CoreBundle\DataContainer\PaletteManipulator;
use Contao\CoreBundle\ServiceAnnotation\Callback;
use Contao\DataContainer;

/**
 * @Callback(table="tl_settings", target="config.onload")
 */
class AddPalleteCallback
{
    public function __invoke(DataContainer $dc = null): void
    {
        $palette = PaletteManipulator::create()
            ->addLegend('cryptmailto_legend', 'uploads_legend', PaletteManipulator::POSITION_AFTER)
            ->addField('cryptmailto_text', 'cryptmailto_legend', PaletteManipulator::POSITION_APPEND)
            ->applyToPalette('default', 'tl_settings');
    }
}
