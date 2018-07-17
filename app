#!/usr/bin/env php
<?php

use App\Application;
use App\Commands\GitRepositoryParser;

require __DIR__ . '/vendor/autoload.php';

$app = new Application;

$app->add(new GitRepositoryParser);

$app->run();
