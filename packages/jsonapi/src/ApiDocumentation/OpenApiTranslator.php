<?php

declare(strict_types=1);

namespace EDT\JsonApi\ApiDocumentation;

use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @deprecated as it is very assumption driven, applications should use their own {@link TranslatorInterface} implementation
 */
class OpenApiTranslator implements TranslatorInterface
{
    public function __construct(
        protected readonly TranslatorInterface $translator
    ) {}

    public function trans(string $id, array $parameters = [], ?string $domain = 'openapi', ?string $locale = 'en'): string
    {
        return trim($this->translator->trans($id, $parameters, $domain, $locale));
    }

    public function getLocale(): string
    {
        return $this->translator->getLocale();
    }
}
