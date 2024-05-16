<?php
declare(strict_types = 1);

use fostercommerce\rector\SetList;
use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        __FILE__,
    ])
    ->withSets([
        SetList::CRAFT_CMS_40
    ]);