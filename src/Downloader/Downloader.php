<?php

namespace App\Downloader;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use ZipArchive;

class Downloader extends Command
{
    const DOWNLOADS_PATH = 'storage';

    const PUBLIC_FUNCTIONS_PATTERN = '/(?P<method>((a|s).*)?public([\w\s]*)?function\s[\w]+\([^)]*\).*)/';

    /** @var ProgressBar */
    protected $progressBar;

    /** @var OutputInterface */
    protected $output;

    public function __construct($output)
    {
        $this->output = $output;
    }

    public function download(string $url, string $filename): string
    {
        $response = $this->getClient()->request('get', $url, [
            'progress' => [$this, 'onProgress'],
        ]);

        $path = $this->getPath($filename);

        if (! file_exists($dir = static::DOWNLOADS_PATH)) {
            mkdir($dir);
        }

        if (! file_exists($dir = dirname($path))) {
            mkdir($dir);
        }

        file_put_contents($path, $response->getBody());

        return $path;
    }

    public function getClient(): ClientInterface
    {
        return new Client;
    }

    /*public function getUrl(string $shortUrl) : string
    {
        
    } */

    public function getPath(string $filename): string
    {
        return static::DOWNLOADS_PATH . DIRECTORY_SEPARATOR . $filename;
    }

    public function onProgress(int $total, int $downloaded): void
    {
        if ($total <= 0) {
            return;
        }

        if (! $this->progressBar) {
            $this->progressBar = $this->createProgressBar(100);
        }

        $this->progressBar->setProgress(100 / $total * $downloaded);
    }

    public function createProgressBar(int $max): ProgressBar
    {
        $bar = new ProgressBar($this->output, $max);

        $bar->setBarCharacter('<fg=green>·</>');
        $bar->setEmptyBarCharacter('<fg=red>·</>');
        $bar->setProgressCharacter('<fg=green>ᗧ</>');
        $bar->setFormat("%current:8s%/%max:-8s% %bar% %percent:5s%% %elapsed:7s%/%estimated:-7s% %memory%");

        return $bar;
    }
    
}
