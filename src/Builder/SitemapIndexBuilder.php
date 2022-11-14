<?php

declare(strict_types=1);

namespace SitemapPlugin\Builder;

use SitemapPlugin\Factory\SitemapIndexFactoryInterface;
use SitemapPlugin\Model\SitemapInterface;
use SitemapPlugin\Provider\IndexUrlProviderInterface;
use SitemapPlugin\Provider\UrlProviderInterface;

final class SitemapIndexBuilder implements SitemapIndexBuilderInterface
{
    private SitemapIndexFactoryInterface $sitemapIndexFactory;

    /** @var UrlProviderInterface[] */
    private array $providers = [];

    /** @var IndexUrlProviderInterface[] */
    private array $indexProviders = [];

    private array $paths = [];

    public function __construct(SitemapIndexFactoryInterface $sitemapIndexFactory)
    {
        $this->sitemapIndexFactory = $sitemapIndexFactory;
    }

    public function addProvider(UrlProviderInterface $provider): void
    {
        $this->providers[] = $provider;
    }

    public function addIndexProvider(IndexUrlProviderInterface $indexProvider): void
    {
        foreach ($this->providers as $provider) {
            $indexProvider->addProvider($provider, $this->paths[$provider->getName()]);
        }

        $this->indexProviders[] = $indexProvider;
    }

    public function addPath(UrlProviderInterface $provider, string $path): void
    {
        if (!array_key_exists($provider->getName(), $this->paths)) {
            $this->paths[$provider->getName()] = [];
        }

        $this->paths[$provider->getName()][] = $path;
    }

    /**
     * {@inheritdoc}
     */
    public function build(): SitemapInterface
    {
        $sitemap = $this->sitemapIndexFactory->createNew();
        $urls = [];

        foreach ($this->indexProviders as $indexProvider) {
            $urls[] = $indexProvider->generate();
        }

        $sitemap->setUrls(\array_merge(...$urls));

        return $sitemap;
    }
}
