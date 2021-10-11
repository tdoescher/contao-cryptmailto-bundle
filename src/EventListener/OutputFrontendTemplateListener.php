<?php

namespace tdoescher\CryptMailtoBundle\EventListener;

use Contao\CoreBundle\ServiceAnnotation\Hook;
use Contao\Config;

/**
 * @Hook("outputFrontendTemplate", priority=-10)
 */
class OutputFrontendTemplateListener
{
    public function __invoke(string $buffer, string $template): string
    {
        $encryptionService = \Contao\System::getContainer()->get('brkwsky.encryption');

        $buffer = \Contao\StringUtil::decodeEntities($buffer);

        preg_match_all('/href="(mailto:[\w.+-]+@[\w.-]+.[a-z]+)".*?>(.*?)<\/a>/i', $buffer, $matches);
        for ($i = 0; $i < count($matches[0]); $i++)
        {
            $link = $matches[0][$i];
            $link = str_replace($matches[1][$i], 'redirect/'.$encryptionService->encryptUrlSafe(str_replace('mailto:', '', $matches[1][$i])), $link);

            $link = str_replace(' target="_blank"', '', $link);
            $link = str_replace(' rel="noopener"', '', $link);

            if(Config::get('cryptmailto_text'))
            {
                $text = Config::get('cryptmailto_text');

                if(preg_match('/[\w\.+-]+@[\w\.-]+\.[a-z]+/i', $matches[2][$i])) {
                    $link = str_replace($matches[2][$i], $text, $link);
                } else if (preg_match('/[\w\.+-]+@@[\w\.-]+\.[a-z]+/i', $matches[2][$i])) {
                    $text = str_replace('@@', '@', $matches[2][$i]);
                    $link = str_replace($matches[2][$i], \Contao\StringUtil::encodeEmail($text), $link);
                }
            }

            $buffer = str_replace($matches[0][$i], $link, $buffer);
        }

        return $buffer;
    }
}
