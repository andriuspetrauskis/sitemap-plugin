<?php

declare(strict_types=1);

namespace SitemapPlugin\Builder;

use SitemapPlugin\Provider\BatchableUrlProviderInterface;
use SitemapPlugin\Provider\UrlProviderInterface;
use Sylius\Component\Core\Model\ChannelInterface;

class SitemapPathBuilder
{
    public function build(
        ChannelInterface $channel,
        UrlProviderInterface $provider,
        ?int $index = null,
        string $suffix = '.xml'
    ): string {
        $subPath = $provider->getName();
        if (null !== $index && $provider instanceof BatchableUrlProviderInterface) {
            $subPath = \sprintf(
                '%s/%s_%d',
                $provider->getName(),
                $provider->getName(),
                $index
            );
        }

        return $this->buildStatic($channel, $subPath, $suffix);
    }

    public function buildByProviderName(
        ChannelInterface $channel,
        string $providerName,
        ?int $index = null,
        string $suffix = '.xml'
    ): string {
        $subPath = $providerName;
        if (null !== $index) {
            $subPath = \sprintf(
                '%s/%s_%d',
                $providerName,
                $providerName,
                $index
            );
        }

        return $this->buildStatic($channel, $subPath, $suffix);
    }

    public function buildStatic(ChannelInterface $channel, string $path, string $suffix = '.xml'): string
    {
        return \sprintf('%s/%s%s', $channel->getCode(), $path, $suffix);
    }
}
