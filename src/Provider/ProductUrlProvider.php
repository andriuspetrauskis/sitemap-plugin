<?php

declare(strict_types=1);

namespace SitemapPlugin\Provider;

use SitemapPlugin\Factory\AlternativeUrlFactoryInterface;
use SitemapPlugin\Factory\UrlFactoryInterface;
use SitemapPlugin\Generator\ResourceImagesToSitemapImagesCollectionGeneratorInterface;
use Sylius\Bundle\CoreBundle\Doctrine\ORM\ProductRepository;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Locale\Context\LocaleContextInterface;
use Symfony\Component\Routing\RouterInterface;

final class ProductUrlProvider extends AbstractBatchableResourceUrlProvider
{
    private ProductRepository $productRepository;

    public function __construct(
        ProductRepository $productRepository,
        RouterInterface $router,
        UrlFactoryInterface $urlFactory,
        AlternativeUrlFactoryInterface $urlAlternativeFactory,
        LocaleContextInterface $localeContext,
        ResourceImagesToSitemapImagesCollectionGeneratorInterface $resourceToImageSitemapArrayGenerator
    ) {
        parent::__construct(
            $router,
            $urlFactory,
            $urlAlternativeFactory,
            $localeContext,
            $resourceToImageSitemapArrayGenerator
        );
        $this->productRepository = $productRepository;
    }

    public function getName(): string
    {
        return 'products';
    }

    protected function getRouteName(): string
    {
        return 'sylius_shop_product_show';
    }

    protected function getResources(ChannelInterface $channel, int $start, int $entries): iterable
    {
        return $this->productRepository->createQueryBuilder('o')
            ->addSelect('translation')
            ->innerJoin('o.translations', 'translation')
            ->andWhere(':channel MEMBER OF o.channels')
            ->andWhere('o.enabled = :enabled')
            ->setParameter('channel', $channel)
            ->setParameter('enabled', true)
            ->setFirstResult($start)
            ->setMaxResults($entries)
            ->getQuery()
            ->getResult();
    }

    protected function count(ChannelInterface $channel): int
    {
        return $this->productRepository->createQueryBuilder('o')
            ->select('count(*)')
            ->innerJoin('o.translations', 'translation')
            ->andWhere(':channel MEMBER OF o.channels')
            ->andWhere('o.enabled = :enabled')
            ->setParameter('channel', $channel)
            ->setParameter('enabled', true)
            ->getQuery()
            ->getSingleColumnResult();
    }
}
