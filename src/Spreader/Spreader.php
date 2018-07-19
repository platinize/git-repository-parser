<?php

namespace App\Spreader;

use ZipArchive;

class Spreader
{
    public function unArchive($path)
    {
        $archive = new ZipArchive();
        $archive->open($path);
        $answer = $archive->extractTo(substr($path, 0, strrpos($path, '/')));
        $archive->close();

        return $answer;
    }
}
