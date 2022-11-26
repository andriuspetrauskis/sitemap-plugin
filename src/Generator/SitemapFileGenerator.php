<?php

declare(strict_types=1);

namespace SitemapPlugin\Generator;

use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use SitemapPlugin\Builder\SitemapBuilderInterface;
use SitemapPlugin\Builder\SitemapIndexBuilderInterface;
use SitemapPlugin\Filesystem\Writer;
use SitemapPlugin\Model\SitemapInterface;
use SitemapPlugin\Provider\BatchableUrlProviderInterface;
use SitemapPlugin\Provider\UrlProviderInterface;
use SitemapPlugin\Renderer\SitemapRendererInterface;
use Sylius\Component\Channel\Repository\ChannelRepositoryInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Symfony\Component\Routing\RouterInterface;

class SitemapFileGenerator implements SitemapFileGeneratorInterface
{
    use LoggerAwareTrait;

    private SitemapBuilderInterface $sitemapBuilder;

    private SitemapIndexBuilderInterface $sitemapIndexBuilder;

    private SitemapRendererInterface $sitemapRenderer;

    private SitemapRendererInterface $sitemapIndexRenderer;

    private Writer $writer;

    private ChannelRepositoryInterface $channelRepository;

    private RouterInterface $router;

    public function __construct(
        SitemapRendererInterface $sitemapRenderer,
        SitemapRendererInterface $sitemapIndexRenderer,
        SitemapBuilderInterface $sitemapBuilder,
        SitemapIndexBuilderInterface $sitemapIndexBuilder,
        Writer $writer,
        ChannelRepositoryInterface $channelRepository,
        RouterInterface $router,
        LoggerInterface $logger
    ) {
        $this->sitemapRenderer = $sitemapRenderer;
        $this->sitemapIndexRenderer = $sitemapIndexRenderer;
        $this->sitemapBuilder = $sitemapBuilder;
        $this->sitemapIndexBuilder = $sitemapIndexBuilder;
        $this->writer = $writer;
        $this->channelRepository = $channelRepository;
        $this->router = $router;
        $this->setLogger($logger);
    }

    public function generate(?array $channels, int $limit): void
    {
        foreach ($this->getFilteredChannels($channels) as $channel) {
            $this->generateForChannel($channel, $limit);
        }
    }

    private function generateForChannel(ChannelInterface $channel, int $limit): void
    {
        $this->logger->info(\sprintf('Start generating sitemaps for channel "%s"', $channel->getName()));

        $this->router->getContext()->setHost($channel->getHostname() ?? 'localhost');
        // TODO make sure providers are every time emptied (reset call or smth?)
        foreach ($this->sitemapBuilder->getProviders() as $provider) {
            $this->generateForChannelProvider($channel, $provider, $limit);
        }

        $this->generateSitemapIndexForChannel($channel);
    }

    private function generateForChannelProvider(ChannelInterface $channel, UrlProviderInterface $provider, int $limit): void
    {
        $this->logger->info(\sprintf('Start generating sitemap "%s" for channel "%s"', $provider->getName(), $channel->getCode()));

        if ($provider instanceof BatchableUrlProviderInterface) {
            $sitemaps = $this->sitemapBuilder->buildBatches($provider, $channel, $limit);
            foreach ($sitemaps as $index => $sitemap) {
                $path = $this->path($channel, \sprintf('%s_%d.xml', $provider->getName(), $index));
                $this->saveXml($path, $sitemap, $provider, $channel, $index);
            }

            return;
        }

        $sitemap = $this->sitemapBuilder->build($provider, $channel);
        $path = $this->path($channel, \sprintf('%s.xml', $provider->getName()));
        $this->saveXml($path, $sitemap, $provider, $channel);
    }

    private function saveXml(
        string $path,
        SitemapInterface $sitemap,
        UrlProviderInterface $provider,
        ChannelInterface $channel,
        int $index = 0
    ): void {
        $xml = $this->sitemapRenderer->render($sitemap);

        $this->writer->write(
            $path,
            $xml
        );

        $this->logger->info(\sprintf(
            'Finished generating sitemap "%s" (%d) for channel "%s" at path "%s"',
            $provider->getName(),
            $index,
            $channel->getCode(),
            $path
        ));

        $this->sitemapIndexBuilder->addPath($provider, $path);
    }

    private function generateSitemapIndexForChannel(ChannelInterface $channel): void
    {
        $this->logger->info(\sprintf('Start generating sitemap index for channel "%s"', $channel->getCode()));

        $sitemap = $this->sitemapIndexBuilder->build();
        $xml = $this->sitemapIndexRenderer->render($sitemap);

        $path = $this->path($channel, 'sitemap_index.xml');

        $this->writer->write(
            $path,
            $xml
        );

        $this->logger->info(\sprintf('Finished generating sitemap index for channel "%s" at path "%s"', $channel->getCode(), $path));
    }

    private function path(ChannelInterface $channel, string $path): string
    {
        return \sprintf('%s/%s', $channel->getCode(), $path);
    }

    /**
     * @return ChannelInterface[]
     */
    private function getFilteredChannels(?array $channels): iterable
    {
        if (self::hasChannelInput($channels)) {
            return $this->channelRepository->findBy(['code' => $channels, 'enabled' => true]);
        }

        return $this->channelRepository->findBy(['enabled' => true]);
    }

    private static function hasChannelInput(?array $channels): bool
    {
        if (\is_array($channels) && 0 === \count($channels)) {
            return false;
        }

        return null !== $channels;
    }
}
