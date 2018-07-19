<?php

namespace App\Commands;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use ZipArchive;
use App\Downloader\Downloader;
use App\Spreader\Spreader;
use App\Parser\Parser;

class GitRepositoryParser extends Command
{

    /** @var ProgressBar */
    protected $progressBar;

    /** @var OutputInterface */
    protected $output;

    public function configure(): void
    {
        $this->setName('search')
           ->setDescription('Search public functions in repository')
           ->addArgument('user/repository', InputArgument::REQUIRED, 'Repository for search in format [User name]/[repository name]');
    }

    public function execute(InputInterface $input, OutputInterface $output): void
    {
        $this->output = $output;

        $url = $this->getUrl($input->getArgument('user/repository'));

        $filename = $this->getFilename($input->getArgument('user/repository'));

        $path = $this->getDownloader()->download($url, $filename);
        
        $this->getSpreader()->unArchive($path);

        $parser = $this->getParser();

        $files = $parser->getFilesFromDir($parser->getDirectory($path));

        $parser->parseFiles($files);
    }

    public function getFilename(string $userRepository): string
    {
        return $userRepository . '.zip';
    }

    public function getDownloader()
    {
        return new Downloader($this->output);
    }

    public function getUrl(string $userRepository): string
    {
        return "https://github.com/{$userRepository}/archive/master.zip";
    }

    public function getSpreader(): Spreader
    {
        return new Spreader;
    }

    public function getParser(): Parser
    {
        return new Parser($this->output);
    }

    
    
}
