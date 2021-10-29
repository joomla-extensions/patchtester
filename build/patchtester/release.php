#!/usr/bin/env php
<?php
/**
 * Script used to prepare a patchtester release
 *
 * This script will:
 *
 * - Replace `__DEPLOY_VERSION__` markers with the version
 * - Update the version number in the component manifest
 * - Update the version in the update server manifest
 *
 * Usage: php build/patchtester/release.php -v <version> --exclude-manifest
 *
 * Examples:
 * - php build/patchtester/release.php -v 3.0.0
 * - php build/patchtester/release.php -v 3.0.1-dev --exclude-manifest
 *
 * @copyright  Copyright (C) 2011 - 2012 Ian MacLennan, Copyright (C) 2013 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later
 */

// Constants.
const PHP_TAB = "\t";

// Functions.
function usage($command)
{
	echo PHP_EOL;
	echo 'Usage: php ' . $command . ' [options]' . PHP_EOL;
	echo PHP_TAB . '[options]:'.PHP_EOL;
	echo PHP_TAB . PHP_TAB . '-v <version>:' . PHP_TAB . 'Version (ex: 3.0.0, 3.0.1-dev)' . PHP_EOL;
	echo PHP_TAB . PHP_TAB . '--exclude-manifest: Exclude updating the update server manifest';
	echo PHP_EOL;
}

$manifestFile     = '/administrator/components/com_patchtester/patchtester.xml';
$updateServerFile = '/manifest.xml';

// Check arguments (exit if incorrect cli arguments).
$opts = getopt('v:', array('exclude-manifest'));

if (empty($opts['v']))
{
	usage($argv[0]);
	die();
}

// Check version string (exit if not correct).
$versionParts = explode('-', $opts['v']);

if (!preg_match('#^[0-9]+\.[0-9]+\.[0-9]+$#', $versionParts[0]))
{
	usage($argv[0]);
	die();
}

if (isset($versionParts[1]) && !preg_match('#(dev|alpha|beta|rc)[0-9]*#', $versionParts[1]))
{
	usage($argv[0]);
	die();
}

if (isset($versionParts[2]) && $versionParts[2] !== 'dev')
{
	usage($argv[0]);
	die();
}

// Make sure we use the correct language and timezone.
setlocale(LC_ALL, 'en_GB');
date_default_timezone_set('Europe/London');

// Make sure file and folder permissions are set correctly.
umask(022);

// Set version properties.
$versionSubParts = explode('.', $versionParts[0]);

$version = array(
	'main'      => $versionSubParts[0] . '.' . $versionSubParts[1],
	'release'   => $versionSubParts[0] . '.' . $versionSubParts[1] . '.' . $versionSubParts[2],
	'dev_devel' => $versionSubParts[2] . (!empty($versionParts[1]) ? '-' . $versionParts[1] : '') . (!empty($versionParts[2]) ? '-' . $versionParts[2] : ''),
	'credate'   => date('d-F-Y'),
);

// Prints version information.
echo PHP_EOL;
echo 'Version data:'. PHP_EOL;
echo '- Main:' . PHP_TAB . PHP_TAB . PHP_TAB . $version['main'] . PHP_EOL;
echo '- Release:' . PHP_TAB . PHP_TAB . $version['release'] . PHP_EOL;
echo '- Full:'  . PHP_TAB . PHP_TAB . PHP_TAB . $version['main'] . '.' . $version['dev_devel'] . PHP_EOL;
echo '- Dev Level:' . PHP_TAB . PHP_TAB . $version['dev_devel'] . PHP_EOL;
echo '- Creation date:' . PHP_TAB . $version['credate'] . PHP_EOL;
echo PHP_EOL;

$rootPath = dirname(dirname(__DIR__));

// Updates the version and creation date in the component manifest file.
if (file_exists($rootPath . $manifestFile))
{
	$fileContents = file_get_contents($rootPath . $manifestFile);
	$fileContents = preg_replace('#<version>[^<]*</version>#', '<version>' . $version['main'] . '.' . $version['dev_devel'] . '</version>', $fileContents);
	$fileContents = preg_replace('#<creationDate>[^<]*</creationDate>#', '<creationDate>' . $version['credate'] . '</creationDate>', $fileContents);
	file_put_contents($rootPath . $manifestFile, $fileContents);
}

// Replaces the `__DEPLOY_VERSION__` marker with the "release" version number
system('cd ' . $rootPath . ' && find administrator -name "*.php" -type f -exec sed -i "s/__DEPLOY_VERSION__/' . $version['release'] . '/g" "{}" \;');

// If not instructed to exclude it, update the update server's manifest
if (!isset($opts['exclude-manifest']))
{
	if (file_exists($rootPath . $updateServerFile))
	{
		$fileContents = file_get_contents($rootPath . $updateServerFile);
		$fileContents = preg_replace('#<infourl title="Patch Tester Component">[^<]*</infourl>#', '<infourl title="Patch Tester Component">https://github.com/joomla-extensions/patchtester/releases/tag/' . $version['release'] . '</infourl>', $fileContents);
		$fileContents = preg_replace('#<downloadurl type="full" format="zip">[^<]*</downloadurl>#', '<downloadurl type="full" format="zip">https://github.com/joomla-extensions/patchtester/releases/download/' . $version['release'] . '/com_patchtester_' . $version['release'] . '.zip</downloadurl>', $fileContents);
		file_put_contents($rootPath . $updateServerFile, $fileContents);

		echo '*************' . PHP_EOL;
		echo '* IMPORTANT *' . PHP_EOL;
		echo '*************' . PHP_EOL;
		echo '' . PHP_EOL;
		echo 'Ensure you regenerate the SHA384 package hash before publishing the updated manifest!!!' . PHP_EOL;
	}
}

echo 'Version bump complete!' . PHP_EOL . PHP_EOL;
