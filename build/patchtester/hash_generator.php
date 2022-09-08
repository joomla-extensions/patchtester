#!/usr/bin/env php
<?php

/**
 * Script used to generate hashes for release packages
 *
 * Usage: php build/patchtester/hash_generator.php
 *
 * @copyright  Copyright (C) 2011 - 2012 Ian MacLennan, Copyright (C) 2013 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later
 */

$packageDir = dirname(__DIR__) . '/packages';

$hashes = array();

/** @var DirectoryIterator $file */
foreach (new DirectoryIterator($packageDir) as $file) {
    if ($file->isDir() || $file->isDot()) {
        continue;
    }

    $hashes[$file->getFilename()] = array(
        'sha384' => hash_file('sha384', $file->getPathname()),
    );
}

$jsonOptions = PHP_VERSION_ID >= 50400 ? JSON_PRETTY_PRINT : 0;

@file_put_contents($packageDir . '/checksums.json', json_encode($hashes, $jsonOptions));

echo 'Checksums file generated' . PHP_EOL . PHP_EOL;
