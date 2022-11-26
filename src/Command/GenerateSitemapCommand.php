<?php

declare(strict_types=1);

namespace SitemapPlugin\Command;

use SitemapPlugin\Generator\SitemapFileGeneratorInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class GenerateSitemapCommand extends Command
{
    private SitemapFileGeneratorInterface $sitemapFileGenerator;

    private const OPTION_CHANNEL = 'channel';
    private const OPTION_LIMIT = 'limit';

    public function __construct(
        SitemapFileGeneratorInterface $sitemapFileGenerator
    ) {
        $this->sitemapFileGenerator = $sitemapFileGenerator;

        parent::__construct('sylius:sitemap:generate');
    }

    protected function configure(): void
    {
        $this->addOption(
            self::OPTION_CHANNEL,
            'c',
            InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL,
            'Channel codes to generate. If none supplied, all channels will generated.'
        );
        $this->addOption(
            self::OPTION_LIMIT,
            'l',
            InputOption::VALUE_REQUIRED,
            'Limit amount of URLs per sitemap',
            50000
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $logger = new ConsoleLogger($output);
        $this->sitemapFileGenerator->setLogger($logger);

        try {
            $this->sitemapFileGenerator->generate(
                $input->getOption(self::OPTION_CHANNEL),
                (int) $input->getOption(self::OPTION_LIMIT)
            );
        } catch (\Throwable $exception) {
            $io->error($exception->getMessage());

            return defined('self::FAILURE') ? self::FAILURE : 1;
        }

        return defined('self::SUCCESS') ? self::SUCCESS : 0;
    }
}
