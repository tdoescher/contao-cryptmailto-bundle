<?php

namespace tdoescher\CryptMailtoBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/redirect/{mailto}", defaults={"_scope" = "frontend"}, name=CryptMailtoController::class)
 */
class CryptMailtoController
{
    public function __invoke(Request $request): RedirectResponse
    {
        $encryptionService = \Contao\System::getContainer()->get('plenta.encryption');

        return new RedirectResponse('mailto:'.$encryptionService->decryptUrlSafe($request->attributes->get('mailto')), 301);
    }
}
