<?php

declare(strict_types=1);

namespace SitemapPlugin\Model;

use DateTimeInterface;
use SitemapPlugin\Exception\SitemapUrlNotFoundException;

final class Sitemap implements SitemapInterface
{
    private iterable $urls = [];

    private ?string $localization = null;

    private ?DateTimeInterface $lastModification = null;

    public function setUrls(iterable $urls): void
    {
        $this->urls = $urls;
    }

    public function getUrls(): iterable
    {
        return $this->urls;
    }

    public function addUrl(UrlInterface $url): void
    {
        if (is_array($this->urls)) {
            $this->urls[] = $url;
        } elseif ($this->urls instanceof \Iterator) {
            $urls = new \AppendIterator();
            $urls->append(new \NoRewindIterator($this->urls));
            $urls->append(new \NoRewindIterator(new \ArrayIterator([$url])));

            $this->urls = $urls;
        }
    }

    public function removeUrl(UrlInterface $url): void
    {
        if (!is_array($this->urls)) {
            throw new \RuntimeException('Could not remove url from iterator, make sure to use arrays');
        }

        $key = \array_search($url, $this->urls, true);
        if (false === $key) {
            throw new SitemapUrlNotFoundException($url);
        }

        unset($this->urls[$key]);
    }

    public function setLocalization(string $localization): void
    {
        $this->localization = $localization;
    }

    public function getLocalization(): ?string
    {
        return $this->localization;
    }

    public function setLastModification(DateTimeInterface $lastModification): void
    {
        $this->lastModification = $lastModification;
    }

    public function getLastModification(): ?DateTimeInterface
    {
        return $this->lastModification;
    }
}
