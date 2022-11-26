<?php

declare(strict_types=1);

namespace SitemapPlugin\Generator;

use Psr\Log\LoggerAwareInterface;

interface SitemapFileGeneratorInterface extends LoggerAwareInterface
{
    public function generate(?array $channels, int $limit): void;
}
