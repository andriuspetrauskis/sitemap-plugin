<?php

declare(strict_types=1);

namespace SitemapPlugin\Provider;

use SitemapPlugin\Factory\IndexUrlFactoryInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Symfony\Component\Routing\RouterInterface;

final class IndexUrlProvider implements IndexUrlProviderInterface
{
    /** @var UrlProviderInterface[] */
    private array $providers = [];

    private RouterInterface $router;

    private IndexUrlFactoryInterface $sitemapIndexUrlFactory;

    public function __construct(
        RouterInterface $router,
        IndexUrlFactoryInterface $sitemapIndexUrlFactory
    ) {
        $this->router = $router;
        $this->sitemapIndexUrlFactory = $sitemapIndexUrlFactory;
    }

    public function addProvider(UrlProviderInterface $provider): void
    {
        $this->providers[] = $provider;
    }

    public function generate(ChannelInterface $channel, int $limit): iterable
    {
        $urls = [];
        foreach ($this->providers as $provider) {
            $urls[] = $this->generateUrlsForProvider($provider, $channel, $limit);
        }

        return array_merge(...$urls);
    }

    private function generateUrlsForProvider(UrlProviderInterface $provider, ChannelInterface $channel, int $limit): iterable
    {
        if ($provider instanceof BatchableUrlProviderInterface) {
            return $this->generateBatchedUrls($provider, $channel, $limit);
        }

        $urls = [];

        $location = $this->router->generate('sylius_sitemap_'.$provider->getName());

        $urls[] = $this->sitemapIndexUrlFactory->createNew($location);

        return $urls;
    }

    private function generateBatchedUrls(BatchableUrlProviderInterface $provider, ChannelInterface $channel, int $limit): iterable
    {
        $batchCount = $provider->getBatchCount($channel, $limit);

        $urls = [];
        if ($batchCount === 0 && !$provider->shouldSkipEmptyFile()) {
            $batchCount = 1;
        }
        for ($i = 0; $i < $batchCount; $i++) {
            $params = ['index' => $i];

            $location = $this->router->generate('sylius_sitemap_'.$provider->getName(), $params);

            $urls[] = $this->sitemapIndexUrlFactory->createNew($location);
        }

        return $urls;
    }
}
