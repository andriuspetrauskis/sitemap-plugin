<?php

declare(strict_types=1);

namespace SitemapPlugin\Controller;

use SitemapPlugin\Builder\SitemapPathBuilder;
use SitemapPlugin\Filesystem\Reader;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Symfony\Component\HttpFoundation\Response;

final class SitemapController extends AbstractController
{
    private ChannelContextInterface $channelContext;
    private SitemapPathBuilder $sitemapPathBuilder;

    public function __construct(
        ChannelContextInterface $channelContext,
        Reader $reader,
        SitemapPathBuilder $sitemapPathBuilder
    ) {
        $this->channelContext = $channelContext;
        $this->sitemapPathBuilder = $sitemapPathBuilder;

        parent::__construct($reader);
    }

    public function showAction(string $name, ?int $index): Response
    {
        $path = $this->sitemapPathBuilder->buildByProviderName(
            $this->channelContext->getChannel(),
            $name,
            $index
        );

        return $this->createResponse($path);
    }
}
