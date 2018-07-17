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

class GitRepositoryParser extends Command
{
    const DOWNLOADS_PATH = 'storage';

    const DOMAIN = 'https://github.com/';

    const ZIP_PATH = '/archive/master.zip';

    const FORMAT = '.zip';

    const PUBLIC_FUNCTIONS_PATTERN = '/(?P<method>((a|s).*)?public([\w\s]*)?function\s[\w]+\([^)]*\).*)/';

    /** @var ProgressBar */
    protected $progressBar;

    /** @var OutputInterface */
    protected $output;

    public function configure(): void
    {
        $this->setName('search')
           ->setDescription('Search public functions in repository')
           ->addArgument('user/repository', InputArgument::REQUIRED,
               'Repository for search in format [User name]/[repository name]');
    }

    public function execute(InputInterface $input, OutputInterface $output): void
    {
        $this->output = $output;

        $url = $this->getUrl($input->getArgument('user/repository'));

        $filename = $this->getFilename($input->getArgument('user/repository'));

        $path = $this->download($url, $filename);

        $this->unArchive($path);

        $files = $this->getFilesFromDir(static::DOWNLOAD_PATH);

        $this->parseFiles($files);
    }

    public function getFilename(string $userRepository): string
    {
        return str_replace('/', '\\', $userRepository) . static::FORMAT;
    }

    public function getUrl(string $userRepository): string
    {
        return static::DOMAIN . $userRepository . static::ZIP_PATH;
    }

    public function download(string $url, string $filename): string
    {
        $response = $this->getClient()->request('get', $url, [
            'progress' => [$this, 'onProgress'],
        ]);

        $this->output->writeln('');

        $path = $this->getPath($filename);

        if (! file_exists($dir = static::DOWNLOADS_PATH)) {
            mkdir($dir);
        }

        if (! file_exists($dir = dirname($path))) {
            mkdir($dir);
        }

        file_put_contents($path, $response->getBody()->getContents());

        return $path;
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

    public function getPath(string $filename): string
    {
        return static::DOWNLOADS_PATH . DIRECTORY_SEPARATOR . $filename;
    }

    public function getClient(): ClientInterface
    {
        return new Client;
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

    public function unArchive($path)
    {
        $archive = new ZipArchive();
        $archive->open($path);
        $answer = $archive->extractTo($this->getDirectory($path));
        $archive->close();
        return $answer;
    }

    public function getDirectory(string $path): string
    {
        return substr($path, 0, strrpos($path, '\\'));
    }

    protected function getFilesFromDir(string $path) : Finder
    {
        $finder = new Finder;

        return $finder->in($this->getDirectory($path))->files()->name('*.php');
    }

    protected function parseFiles(Finder $files) : void
    {
        foreach ($files as $file) {
            $path = $file->getRelativePathname();
            $content = $file->getContents();
            $methodsList = $this->parsePublicMethods($content);

            if (!count($methodsList)) {
                continue;
            }

            $this->output->writeln($path);
            foreach ($methodsList as $method) {
                $this->output->writeln("\t" . $method);
            }
        }
    }

    protected function parsePublicMethods(string $content) : array
    {
        preg_match_all(static::PUBLIC_FUNCTIONS_PATTERN, $content, $matches);

        return $matches['method'];
    }
}
