<?php

declare(strict_types=1);

namespace SitemapPlugin\Builder;

use SitemapPlugin\Model\SitemapInterface;
use SitemapPlugin\Provider\BatchableUrlProviderInterface;
use SitemapPlugin\Provider\UrlProviderInterface;
use Sylius\Component\Core\Model\ChannelInterface;

interface SitemapBuilderInterface extends BuilderInterface
{
    public function build(UrlProviderInterface $provider, ChannelInterface $channel): SitemapInterface;

    public function buildBatches(
        BatchableUrlProviderInterface $provider,
        ChannelInterface $channel,
        int $batchSize
    ): iterable;

    /**
     * @return UrlProviderInterface[]
     */
    public function getProviders(): iterable;
}
