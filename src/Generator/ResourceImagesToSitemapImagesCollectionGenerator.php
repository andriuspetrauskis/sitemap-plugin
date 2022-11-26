<?php

declare(strict_types=1);

namespace SitemapPlugin\Generator;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use SitemapPlugin\Factory\ImageFactoryInterface;
use Sylius\Component\Core\Model\ImagesAwareInterface;

final class ResourceImagesToSitemapImagesCollectionGenerator implements ResourceImagesToSitemapImagesCollectionGeneratorInterface
{
    private CacheManager $imagineCacheManager;

    private ImageFactoryInterface $sitemapImageUrlFactory;

    private string $imagePreset = 'sylius_shop_product_original';

    public function __construct(
        ImageFactoryInterface $sitemapImageUrlFactory,
        CacheManager $imagineCacheManager,
        ?string $imagePreset = null
    ) {
        $this->sitemapImageUrlFactory = $sitemapImageUrlFactory;
        $this->imagineCacheManager = $imagineCacheManager;

        if (null !== $imagePreset) {
            $this->imagePreset = $imagePreset;
        }
    }

    public function generate(ImagesAwareInterface $resource): Collection
    {
        $images = new ArrayCollection();

        foreach ($resource->getImages() as $image) {
            $path = $image->getPath();

            if (null === $path) {
                continue;
            }

            $sitemapImage = $this->sitemapImageUrlFactory->createNew($this->imagineCacheManager->getBrowserPath($path, $this->imagePreset));

            /**
             * @psalm-suppress InvalidArgument
             */
            $images->add($sitemapImage);
        }

        return $images;
    }
}
