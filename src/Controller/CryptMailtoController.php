<?php

declare(strict_types=1);

namespace tdoescher\CryptMailtoBundle\Controller;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use tdoescher\CryptMailtoBundle\Encryption\MailtoEncrypter;

#[Route('/redirect/{mailto}', name: self::class, requirements: ['mailto' => '[A-Za-z0-9_-]+'], defaults: ['_scope' => 'frontend'])]
class CryptMailtoController
{
    public function __construct(private readonly MailtoEncrypter $encrypter)
    {
    }

    public function __invoke(string $mailto): RedirectResponse
    {
        $email = $this->encrypter->decrypt($mailto);

        if (null === $email || '' === $email) {
            throw new NotFoundHttpException('Invalid mailto link.');
        }

        $response = new RedirectResponse('mailto:'.$email, 301);
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow');

        return $response;
    }
}
