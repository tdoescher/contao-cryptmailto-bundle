<?php

declare(strict_types=1);

namespace tdoescher\CryptMailtoBundle\EventListener;

use Contao\Config;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\InsertTag\InsertTagParser;
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
    private const EMAIL_PATTERN = '/^[^\s@]+@[^\s@]+\.[a-z]{2,}$/iu';

    private const DOUBLE_AT_PATTERN = '/^[^\s@]+@@[^\s@]+\.[a-z]{2,}$/iu';

    private string|null $replacementText = null;

    public function __construct(
        private readonly ScopeMatcher $scopeMatcher,
        private readonly ContaoFramework $framework,
        private readonly MailtoEncrypter $encrypter,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly InsertTagParser $insertTagParser,
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
        $this->replacementText = null;

        $replaced = preg_replace_callback(
            '/<a\b([^>]*?)\shref="([^"]*)"([^>]*)>(.*?)<\/a>/is',
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
        [$link, $before, $href, $after, $content] = $match;

        $href = $this->decode($href);

        if (!str_starts_with(strtolower($href), 'mailto:')) {
            return $link;
        }

        $stringUtil = $this->framework->getAdapter(StringUtil::class);
        $email = substr($href, 7);

        // "@@" im Linkziel: keine Weiterleitung, nur ein normaler (von Contao verschleierter) mailto-Link
        if (str_contains($email, '@@')) {
            $href = $stringUtil->encodeEmail('mailto:'.str_replace('@@', '@', $email));

            return \sprintf('<a%s href="%s"%s>%s</a>', $before, $href, $after, $this->replaceInText($content, false));
        }

        $url = $this->urlGenerator->generate(CryptMailtoController::class, [
            'mailto' => $this->encrypter->encrypt($email),
        ]);

        return \sprintf('<a href="%s"%s>%s</a>', $url, $this->removeAttributes($before.$after), $this->replaceInText($content, true));
    }

    /**
     * Ersetzt E-Mail-Adressen nur in den Textknoten des Linkinhalts, damit
     * Markup wie Icons (z. B. aus {{svgicon::…}}) oder <span>-Elemente erhalten bleibt.
     *
     * - "info@@example.com" wird zu einer verschleierten, sichtbaren Adresse
     * - "info@example.com" wird (falls konfiguriert) durch den Ersatztext ersetzt
     */
    private function replaceInText(string $content, bool $useReplacement): string
    {
        $parts = preg_split('/(<[^>]*>)/', $content, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [$content];

        foreach ($parts as $i => $part) {
            if ('' === $part || '<' === $part[0]) {
                continue;
            }

            $text = $this->normalize($this->decode($part));

            if (preg_match(self::DOUBLE_AT_PATTERN, $text)) {
                $parts[$i] = $this->keepWhitespace($part, $this->framework->getAdapter(StringUtil::class)->encodeEmail(str_replace('@@', '@', $text)));
            } elseif ($useReplacement && preg_match(self::EMAIL_PATTERN, $text) && '' !== ($replacement = $this->getReplacementText())) {
                $parts[$i] = $this->keepWhitespace($part, $replacement);
            }
        }

        return implode('', $parts);
    }

    private function decode(string $value): string
    {
        return html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    private function normalize(string $text): string
    {
        // Geschützte Leerzeichen und unsichtbare Zeichen (z. B. aus TinyMCE) entfernen
        $text = preg_replace('/[\x{00A0}\x{00AD}\x{200B}-\x{200D}\x{FEFF}]/u', '', $text) ?? $text;

        return trim($text);
    }

    private function keepWhitespace(string $original, string $replacement): string
    {
        preg_match('/^(\s*)/', $original, $lead);
        preg_match('/(\s*)$/', $original, $trail);

        return $lead[1].$replacement.$trail[1];
    }

    private function getReplacementText(): string
    {
        if (null === $this->replacementText) {
            $text = trim((string) $this->framework->getAdapter(Config::class)->get('cryptmailto_text'));

            // Insert-Tags im Ersatztext auflösen (z. B. {{iflng::de}}…{{iflng}})
            $this->replacementText = '' === $text ? '' : trim($this->insertTagParser->replaceInline($text));
        }

        return $this->replacementText;
    }

    private function removeAttributes(string $attributes): string
    {
        $attributes = preg_replace('/\s+(target="_blank"|rel="noopener")/i', '', $attributes);

        return rtrim(' '.trim($attributes));
    }
}
