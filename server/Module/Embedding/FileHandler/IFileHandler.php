<?php

declare(strict_types=1);

namespace app\Module\Embedding\FileHandler;

interface IFileHandler
{
    public function parseSections(string $content, int $maxSectionTokens): \Generator;
}
