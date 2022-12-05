<?php

declare(strict_types=1);

namespace SitemapPlugin\Provider;

use Sylius\Component\Core\Model\ChannelInterface;

interface BatchableUrlProviderInterface extends UrlProviderInterface
{
    public function generateBatches(ChannelInterface $channel, int $batchSize): iterable;
    public function getBatchCount(ChannelInterface $channel, int $bathSize): int;
    public function shouldSkipEmptyFile(): bool;
}
