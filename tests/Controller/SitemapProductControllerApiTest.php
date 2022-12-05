<?php

declare(strict_types=1);

namespace Tests\SitemapPlugin\Controller;

final class SitemapProductControllerApiTest extends XmlApiTestCase
{
    public function testShowActionResponse()
    {
        $this->loadFixturesFromFiles(['channel.yaml', 'product.yaml']);
        $this->generateSitemaps([
            '--limit' => 2,
        ]);

        $response = $this->getBufferedResponse('/sitemap/products/products_0.xml');
        $this->assertResponse($response, 'show_sitemap_products_0');
        $response = $this->getBufferedResponse('/sitemap/products/products_1.xml');
        $this->assertResponse($response, 'show_sitemap_products_1');
        $this->deleteSitemaps();
    }
}
