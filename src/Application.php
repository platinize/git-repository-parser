<?php

namespace App;

use Symfony\Component\Console\Application as SymfonyApplication;

class Application extends SymfonyApplication
{
    const NAME = 'CLI';
    const VERSION = '1.0';

    public function __construct()
    {
        parent::__construct(static::NAME, static::VERSION);
    }
}
