<?php

declare(strict_types=1);

namespace SitemapPlugin\Generator;

use Doctrine\Common\Collections\Collection;
use Sylius\Component\Core\Model\ImagesAwareInterface;

interface ResourceImagesToSitemapImagesCollectionGeneratorInterface
{
    public function generate(ImagesAwareInterface $resource): Collection;
}
