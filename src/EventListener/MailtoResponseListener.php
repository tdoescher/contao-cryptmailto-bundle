<?php

declare(strict_types=1);

namespace tdoescher\CryptMailtoBundle\EventListener;

use Contao\Config;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\StringUtil;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use tdoescher\CryptMailtoBundle\Controller\CryptMailtoController;
use tdoescher\CryptMailtoBundle\Encryption\MailtoEncrypter;

/**
 * Ersetzt mailto-Links in allen Frontend-Seiten, unabhängig vom Layout-Typ
 * (klassisches Layout und modernes Twig-Layout).
 */
#[AsEventListener(KernelEvents::RESPONSE, priority: -10)]
class MailtoResponseListener
{
    private const EMAIL_PATTERN = '/^[\w.+-]+@[\w.-]+\.[a-z]+$/i';

    private const DOUBLE_AT_PATTERN = '/^[\w.+-]+@@[\w.-]+\.[a-z]+$/i';

    public function __construct(
        private readonly ScopeMatcher $scopeMatcher,
        private readonly ContaoFramework $framework,
        private readonly MailtoEncrypter $encrypter,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function __invoke(ResponseEvent $event): void
    {
        if (!$event->isMainRequest() || !$this->scopeMatcher->isFrontendRequest($event->getRequest())) {
            return;
        }

        $response = $event->getResponse();

        if ($response instanceof StreamedResponse || $response instanceof BinaryFileResponse) {
            return;
        }

        $contentType = (string) $response->headers->get('Content-Type', 'text/html');

        if (!str_contains($contentType, 'text/html')) {
            return;
        }

        $content = $response->getContent();

        if (false === $content || !str_contains(strtolower($content), 'href')) {
            return;
        }

        $this->framework->initialize();

        $replaced = preg_replace_callback(
            '/<a\b([^>]*?)\bhref="([^"]*)"([^>]*)>(.*?)<\/a>/is',
            fn (array $match): string => $this->replaceLink($match),
            $content,
        );

        if (null !== $replaced && $replaced !== $content) {
            $response->setContent($replaced);

            if ($response->headers->has('Content-Length')) {
                $response->headers->set('Content-Length', (string) \strlen($replaced));
            }
        }
    }

    private function replaceLink(array $match): string
    {
        [$link, $before, $href, $after, $text] = $match;

        $href = html_entity_decode($href, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        if (!str_starts_with(strtolower($href), 'mailto:')) {
            return $link;
        }

        $url = $this->urlGenerator->generate(CryptMailtoController::class, [
            'mailto' => $this->encrypter->encrypt(substr($href, 7)),
        ]);

        $attributes = $this->removeAttributes($before.$after);
        $plainText = trim(html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        if (preg_match(self::DOUBLE_AT_PATTERN, $plainText)) {
            $text = $this->framework->getAdapter(StringUtil::class)->encodeEmail(str_replace('@@', '@', $plainText));
        } elseif (
            ($replacement = (string) $this->framework->getAdapter(Config::class)->get('cryptmailto_text'))
            && preg_match(self::EMAIL_PATTERN, $plainText)
        ) {
            $text = $replacement;
        }

        return \sprintf('<a href="%s"%s>%s</a>', $url, $attributes, $text);
    }

    private function removeAttributes(string $attributes): string
    {
        $attributes = preg_replace('/\s+(target="_blank"|rel="noopener")/i', '', $attributes);

        return rtrim(' '.trim($attributes));
    }
}
