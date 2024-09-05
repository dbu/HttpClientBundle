<?php

declare(strict_types=1);

namespace Http\HttplugBundle\Collector\Twig;

use Symfony\Component\VarDumper\Cloner\ClonerInterface;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\DataDumperInterface;
use Symfony\Component\VarDumper\Dumper\HtmlDumper;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
final class HttpMessageMarkupExtension extends AbstractExtension
{
    private ClonerInterface $cloner;

    private DataDumperInterface $dumper;

    public function __construct(?ClonerInterface $cloner = null, ?DataDumperInterface $dumper = null)
    {
        $this->cloner = $cloner ?: new VarCloner();
        $this->dumper = $dumper ?: new HtmlDumper();
    }

    /**
     * @return TwigFilter[]
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('httplug_markup_body', $this->markupBody(...), ['is_safe' => ['html']]),
        ];
    }

    public function markupBody(string $body): ?string
    {
        if ('' !== $body && in_array($body[0], ['{', '['], true)) {
            try {
                $body = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException) {
                // ignore
            }
        }

        return $this->dumper->dump($this->cloner->cloneVar($body));
    }

    public function getName(): string
    {
        return 'httplug.message_markup';
    }
}
