<?php

namespace App\Parser;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

class Parser
{
    const PUBLIC_FUNCTIONS_PATTERN = '/(?P<method>((a|s).*)?public([\w\s]*)?function\s[\w]+\([^)]*\).*)/';

    public $output;

    public function __construct($output)
    {
        $this->output = $output;
    }

    public function getDirectory(string $path): string
    {
        return substr($path, 0, strrpos($path, '/'));
    }

    public function getFilesFromDir(string $path) : Finder
    {
        $finder = new Finder;

        return $finder->in($this->getDirectory($path))->files()->name('*.php');
    }

    public function parseFiles(Finder $files) : void
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

    public function parsePublicMethods(string $content) : array
    {
        preg_match_all(static::PUBLIC_FUNCTIONS_PATTERN, $content, $matches);

        return $matches['method'];
    }
}
