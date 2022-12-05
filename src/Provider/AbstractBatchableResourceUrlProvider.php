<?php

declare(strict_types=1);

namespace SitemapPlugin\Provider;

use Doctrine\Common\Collections\Collection;
use SitemapPlugin\Factory\AlternativeUrlFactoryInterface;
use SitemapPlugin\Factory\UrlFactoryInterface;
use SitemapPlugin\Generator\ResourceImagesToSitemapImagesCollectionGeneratorInterface;
use SitemapPlugin\Model\ChangeFrequency;
use SitemapPlugin\Model\UrlInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ImagesAwareInterface;
use Sylius\Component\Locale\Context\LocaleContextInterface;
use Sylius\Component\Locale\Model\LocaleInterface;
use Sylius\Component\Resource\Model\ResourceInterface;
use Sylius\Component\Resource\Model\SlugAwareInterface;
use Sylius\Component\Resource\Model\TimestampableInterface;
use Sylius\Component\Resource\Model\TranslatableInterface;
use Sylius\Component\Resource\Model\TranslationInterface;
use Symfony\Component\Routing\RouterInterface;

abstract class AbstractBatchableResourceUrlProvider implements BatchableUrlProviderInterface
{
    /** @var array<string|null> */
    protected array $channelLocaleCodes;

    protected RouterInterface $router;

    protected UrlFactoryInterface $urlFactory;

    protected AlternativeUrlFactoryInterface $urlAlternativeFactory;

    protected LocaleContextInterface $localeContext;

    protected ResourceImagesToSitemapImagesCollectionGeneratorInterface $resourceToImageSitemapArrayGenerator;

    public function __construct(
        RouterInterface $router,
        UrlFactoryInterface $urlFactory,
        AlternativeUrlFactoryInterface $urlAlternativeFactory,
        LocaleContextInterface $localeContext,
        ResourceImagesToSitemapImagesCollectionGeneratorInterface $resourceToImageSitemapArrayGenerator
    ) {
        $this->router = $router;
        $this->urlFactory = $urlFactory;
        $this->urlAlternativeFactory = $urlAlternativeFactory;
        $this->localeContext = $localeContext;
        $this->resourceToImageSitemapArrayGenerator = $resourceToImageSitemapArrayGenerator;
    }

    public function generateBatches(ChannelInterface $channel, int $batchSize): iterable
    {
        $batches = $this->getBatchCount($channel, $batchSize);

        for ($i = 0; $i < $batches; $i++) {
            yield $this->generateBatch($channel, $i * $batchSize, $batchSize);
        }
    }

    public function generate(ChannelInterface $channel): iterable
    {
        return $this->generateBatch($channel, 0, PHP_INT_MAX);
    }

    public function getBatchCount(ChannelInterface $channel, int $bathSize): int
    {
        $count = $this->count($channel);

        return (int) ceil($count / $bathSize);
    }

    public function shouldSkipEmptyFile(): bool
    {
        // If set to false, will generate files despite if there are anything in it
        // If set to true, will skip generating empty files
        return false;
    }

    protected function generateBatch(ChannelInterface $channel, int $start, int $entries): iterable
    {
        foreach ($this->getResources($channel, $start, $entries) as $resource) {
            yield $this->createUrl($resource, $channel);
        }
    }

    protected function createUrl(ResourceInterface $resource, ChannelInterface $channel): UrlInterface
    {
        $resourceUrl = $this->urlFactory->createNew(
            ($resource instanceof SlugAwareInterface && !$resource instanceof TranslatableInterface) ?
                $this->generateResourceUrlBySlug($resource) : $this->generateResourceUrl($resource)
        );
        $resourceUrl->setChangeFrequency(ChangeFrequency::always());
        $resourceUrl->setPriority(0.5);
        if ($resource instanceof TimestampableInterface && $resource->getUpdatedAt() !== null) {
            $resourceUrl->setLastModification($resource->getUpdatedAt());
        }
        if ($resource instanceof ImagesAwareInterface) {
            $resourceUrl->setImages($this->resourceToImageSitemapArrayGenerator->generate($resource));
        }

        if ($resource instanceof TranslatableInterface) {
            /** @var TranslationInterface $translation */
            foreach ($this->getTranslations($resource, $channel) as $translation) {
                $locale = $translation->getLocale();

                if ($locale === null) {
                    continue;
                }

                if (!$this->localeInLocaleCodes($translation, $channel)) {
                    continue;
                }

                $location = $this->generateResourceTranslationUrl($translation);

                if ($locale === $this->localeContext->getLocaleCode()) {
                    $resourceUrl->setLocation($location);

                    continue;
                }

                $resourceUrl->addAlternative($this->urlAlternativeFactory->createNew($location, $locale));
            }
        }

        return $resourceUrl;
    }

    protected function getTranslations(TranslatableInterface $resource, ChannelInterface $channel): Collection
    {
        return $resource->getTranslations()->filter(
            fn (TranslationInterface $translation): bool => $this->localeInLocaleCodes($translation, $channel)
        );
    }

    protected function localeInLocaleCodes(TranslationInterface $translation, ChannelInterface $channel): bool
    {
        return \in_array($translation->getLocale(), $this->getLocaleCodes($channel), true);
    }

    protected function getLocaleCodes(ChannelInterface $channel): array
    {
        $channelCode = $channel->getCode();
        if (!isset($this->channelLocaleCodes[$channelCode])) {
            $this->channelLocaleCodes[$channelCode] = $channel->getLocales()->map(
                fn (LocaleInterface $locale): ?string => $locale->getCode()
            )->toArray();
        }

        return $this->channelLocaleCodes[$channelCode];
    }

    protected function generateResourceUrlBySlug(SlugAwareInterface $resource): string
    {
        return $this->router->generate($this->getRouteName(), [
            'slug' => $resource->getSlug(),
        ]);
    }

    protected function generateResourceUrl(ResourceInterface $resource): string
    {
        return '';
    }

    protected function generateResourceTranslationUrl(TranslationInterface $translation): string
    {
        $parameters = [
            '_locale' => $translation->getLocale(),
        ];
        if ($translation instanceof SlugAwareInterface) {
            $parameters['slug'] = $translation->getSlug();
        }

        return $this->router->generate($this->getRouteName(), $parameters);
    }

    abstract protected function getResources(ChannelInterface $channel, int $start, int $entries): iterable;
    abstract protected function count(ChannelInterface $channel): int;
    abstract protected function getRouteName(): string;
}
