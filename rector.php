<?php

declare(strict_types=1);

use Rector\Set\ValueObject\SetList;
use Rector\Config\RectorConfig;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->sets([
        SetList::DEAD_CODE,
        SetList::CODE_QUALITY,
        SetList::TYPE_DECLARATION,
    ]);

    $rectorConfig->paths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ]);
};


