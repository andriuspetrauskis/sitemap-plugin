<?php

declare(strict_types=1);

namespace SitemapPlugin\Builder;

use SitemapPlugin\Factory\SitemapFactoryInterface;
use SitemapPlugin\Model\SitemapInterface;
use SitemapPlugin\Provider\BatchableUrlProviderInterface;
use SitemapPlugin\Provider\UrlProviderInterface;
use Sylius\Component\Core\Model\ChannelInterface;

final class SitemapBuilder implements SitemapBuilderInterface
{
    private SitemapFactoryInterface $sitemapFactory;

    /** @var UrlProviderInterface[] */
    private array $providers = [];

    public function __construct(SitemapFactoryInterface $sitemapFactory)
    {
        $this->sitemapFactory = $sitemapFactory;
    }

    public function addProvider(UrlProviderInterface $provider): void
    {
        $this->providers[] = $provider;
    }

    public function getProviders(): iterable
    {
        return $this->providers;
    }

    public function build(UrlProviderInterface $provider, ChannelInterface $channel): SitemapInterface
    {
        $urls = [];

        $sitemap = $this->sitemapFactory->createNew();
        $urls[] = $provider->generate($channel);

        $sitemap->setUrls(\array_merge(...$urls));

        return $sitemap;
    }

    public function buildBatches(
        BatchableUrlProviderInterface $provider,
        ChannelInterface $channel,
        int $batchSize
    ): iterable {
        $batches = $provider->generateBatches($channel, $batchSize);

        foreach ($batches as $batch) {
            $sitemap = $this->sitemapFactory->createNew();
            $sitemap->setUrls($batch);

            yield $sitemap;
        }
    }
}
