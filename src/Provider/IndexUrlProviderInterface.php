<?php

declare(strict_types=1);

namespace SitemapPlugin\Provider;

interface IndexUrlProviderInterface
{
    public function generate(): iterable;

    public function addProvider(UrlProviderInterface $provider, array $paths = []): void;
}
