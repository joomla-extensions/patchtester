<?php

/**
 * Patch testing component for the Joomla! CMS
 *
 * @copyright  Copyright (C) 2011 - 2012 Ian MacLennan, Copyright (C) 2013 - 2025 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later
 */

namespace Joomla\Component\Patchtester\Administrator\Model;

use Joomla\Archive\Zip;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Http\HttpFactory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Version;
use Joomla\Component\Patchtester\Administrator\Exception\UnexpectedResponse;
use Joomla\Component\Patchtester\Administrator\GitHub\GitHub;
use Joomla\Component\Patchtester\Administrator\GithubCredentialsTrait;
use Joomla\Component\Patchtester\Administrator\Helper\Helper;
use Joomla\Filesystem\File;
use Joomla\Filesystem\Folder;
use Joomla\Filesystem\Path;
use Joomla\Registry\Registry;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Methods supporting pull requests.
 *
 * @since  2.0
 */
class PullModel extends BaseDatabaseModel
{
    use GithubCredentialsTrait;

    /**
     * Array containing top level non-production folders
     *
     * @var    array
     * @since  2.0
     */
    protected array $nonProductionFolders
        = [
            'build',
            'docs',
            'installation',
            'tests',
            '.github',
        ];

    /**
     * Array containing non-production files
     *
     * @var    array
     * @since  2.0
     */
    protected array $nonProductionFiles
        = [
            '.drone.yml',
            '.gitignore',
            '.php_cs',
            '.travis.yml',
            'README.md',
            'build.xml',
            'composer.json',
            'composer.lock',
            'phpunit.xml.dist',
            'robots.txt.dist',
            'travisci-phpunit.xml',
            'LICENSE',
            'RoboFile.dist.ini',
            'RoboFile.php',
            'codeception.yml',
            'jorobo.dist.ini',
            'manifest.xml',
            'crowdin.yaml',
        ];

    /**
     * The namespace mapper
     *
     * @var    \JNamespacePsr4Map
     * @since  4.0.0
     */
    protected \JNamespacePsr4Map $namespaceMapper;

    /**
     * @inheritDoc
     *
     * @since  4.0.0
     */
    public function __construct(
        $config = [],
        ?MVCFactoryInterface $factory = null
    ) {
        parent::__construct($config, $factory);

        $this->state = $this->getCredentials();

        $this->namespaceMapper = new \JNamespacePsr4Map();
    }

    /**
     * Patches the code with the supplied pull request
     * However uses different methods for different repositories.
     *
     * @param   int  $id  ID of the pull request to apply
     *
     * @return  bool
     *
     * @throws  \RuntimeException|\Exception
     * @since   3.0
     *
     */
    public function apply(int $id): bool
    {
        $params = ComponentHelper::getParams('com_patchtester');
        // Decide based on repository settings whether patch will be applied through Github or CIServer
        if ((bool)$params->get('ci_switch', 0)) {
            return $this->applyWithCIServer($id);
        }

        return $this->applyWithGitHub($id);
    }

    /**
     * Patches the code with the supplied pull request
     *
     * @param   int  $id  ID of the pull request to apply
     *
     * @return  bool
     *
     * @throws  \RuntimeException|\Exception
     * @since   3.0
     *
     */
    private function applyWithCIServer(int $id): bool
    {
        // Get the CIServer Registry
        $ciSettings = Helper::initializeCISettings();
        // Get the Github object
        $github = Helper::initializeGithub();
        // Retrieve pullData for sha later on.
        $pull = $this->retrieveGitHubData($github, $id);
        if ($pull->head->repo === null) {
            throw new \RuntimeException(Text::_('COM_PATCHTESTER_REPO_IS_GONE'));
        }

        $sha = $pull->head->sha;
        // Create tmp folder if it does not exist
        if (!file_exists($ciSettings->get('folder.temp'))) {
            Folder::create($ciSettings->get('folder.temp'));
        }

        $tempPath      = $ciSettings->get('folder.temp') . '/' . $id;
        $backupsPath   = $ciSettings->get('folder.backups') . '/' . $id;
        $delLogPath    = $tempPath . '/' . $ciSettings->get('zip.log.name');
        $zipPath       = $tempPath . '/' . $ciSettings->get('zip.name');
        $serverZipPath = \sprintf($ciSettings->get('zip.url'), $id);
        // Patch has already been applied
        if (file_exists($backupsPath)) {
            return false;
        }

        $version    = new Version();
        $httpOption = new Registry();
        $httpOption->set(
            'userAgent',
            $version->getUserAgent('Joomla', true, false)
        );

        // Try to download the zip file
        try {
            $http   = HttpFactory::getHttp($httpOption);
            $result = $http->get($serverZipPath);
        } catch (\RuntimeException) {
            $result = null;
        }

        if (
            $result === null
            || ($result->getStatusCode() !== 200
                && $result->getStatusCode() !== 310)
        ) {
            throw new \RuntimeException(
                Text::_('COM_PATCHTESTER_SERVER_RESPONDED_NOT_200')
            );
        }

        // Assign to variable to avlod PHP notice "Indirect modification of overloaded property"
        $content = (string)$result->getBody();
        // Write the file to disk
        File::write($zipPath, $content);
        // Check if zip folder could have been downloaded
        if (!file_exists($zipPath)) {
            throw new \RuntimeException(
                Text::_('COM_PATCHTESTER_ZIP_DOES_NOT_EXIST')
            );
        }

        Folder::create($tempPath);
        $zip = new Zip();
        if (!$zip->extract($zipPath, $tempPath)) {
            Folder::delete($tempPath);
            throw new \RuntimeException(
                Text::_('COM_PATCHTESTER_ZIP_EXTRACT_FAILED')
            );
        }

        // Remove zip to avoid get listing afterwards
        File::delete($zipPath);
        // Verify the composer autoloader for any invalid entries
        if ($this->verifyAutoloader($tempPath) === false) {
            // There is something broken in the autoloader, clean up and go back
            Folder::delete($tempPath);
            throw new \RuntimeException(
                Text::_('COM_PATCHTESTER_PATCH_BREAKS_SITE')
            );
        }

        // Get files from deleted_logs
        $deletedFiles = (file_exists($delLogPath) ? file($delLogPath) : []);
        $deletedFiles = array_map('trim', $deletedFiles);
        if (file_exists($delLogPath)) {
            // Remove deleted_logs to avoid get listing afterwards
            File::delete($delLogPath);
        }

        // Retrieve all files and merge them into one array
        $files = Folder::files($tempPath, null, true, true);
        $files = str_replace(Path::clean($tempPath . '\\'), '', $files);
        $files = array_merge($files, $deletedFiles);
        Folder::create($backupsPath);
        // Moves existent files to backup and replace them or creates new one if they don't exist
        foreach ($files as $key => $file) {
            try {
                $filePath = explode(DIRECTORY_SEPARATOR, Path::clean($file));
                array_pop($filePath);
                $filePath = implode(DIRECTORY_SEPARATOR, $filePath);
                // Deleted_logs returns files as well as folder, if value is folder, unset and skip
                if (is_dir(JPATH_ROOT . '/' . $file)) {
                    unset($files[$key]);
                    continue;
                }

                if (file_exists(JPATH_ROOT . '/' . $file)) {
                    // Create directories if they don't exist until file
                    if (!file_exists($backupsPath . '/' . $filePath)) {
                        Folder::create($backupsPath . '/' . $filePath);
                    }

                    File::move(
                        JPATH_ROOT . '/' . $file,
                        $backupsPath . '/' . $file
                    );
                }

                if (file_exists($tempPath . '/' . $file)) {
                    // Create directories if they don't exist until file
                    if (
                        !file_exists(JPATH_ROOT . '/' . $filePath)
                        || !is_dir(
                            JPATH_ROOT . '/' . $filePath
                        )
                    ) {
                        Folder::create(JPATH_ROOT . '/' . $filePath);
                    }

                    File::copy(
                        $tempPath . '/' . $file,
                        JPATH_ROOT . '/' . $file
                    );
                }
            } catch (\RuntimeException $exception) {
                Folder::delete($tempPath);
                Folder::move($backupsPath, $backupsPath . '_failed');

                throw new \RuntimeException(
                    Text::sprintf(
                        'COM_PATCHTESTER_FAILED_APPLYING_PATCH',
                        $file,
                        $exception->getMessage()
                    )
                );
            }
        }

        // Clear temp folder and store applied patch in database
        Folder::delete($tempPath);
        $this->saveAppliedPatch($id, $files, $sha);
        // Update the autoloader file
        $this->namespaceMapper->create();
        // Change the media version
        $version = new Version();
        $version->refreshMediaVersion();

        return true;
    }

    /**
     * Patches the code with the supplied pull request
     *
     * @param   GitHub   $github  github object
     * @param   int      $id      Id of the pull request
     *
     * @return  \stdClass The pull request data
     *
     * @throws  \RuntimeException
     * @since   2.0
     *
     */
    private function retrieveGitHubData(GitHub $github, int $id): \stdClass
    {
        try {
            $rateResponse = $github->getRateLimit();
            $rate         = json_decode((string) $rateResponse->getBody(), false);
        } catch (UnexpectedResponse $exception) {
            throw new \RuntimeException(
                Text::sprintf(
                    'COM_PATCHTESTER_COULD_NOT_CONNECT_TO_GITHUB',
                    $exception->getMessage()
                ),
                $exception->getCode(),
                $exception
            );
        }

        // If over the API limit, we can't build this list
        if ((int)$rate->resources->core->remaining === 0) {
            throw new \RuntimeException(
                Text::sprintf(
                    'COM_PATCHTESTER_API_LIMIT_LIST',
                    Factory::getDate($rate->resources->core->reset)
                )
            );
        }

        try {
            $pullResponse = $github->getPullRequest(
                $this->getState()->get('github_user'),
                $this->getState()->get('github_repo'),
                $id
            );
            $pull         = json_decode((string) $pullResponse->getBody(), false);
        } catch (UnexpectedResponse $exception) {
            throw new \RuntimeException(
                Text::sprintf(
                    'COM_PATCHTESTER_COULD_NOT_CONNECT_TO_GITHUB',
                    $exception->getMessage()
                ),
                $exception->getCode(),
                $exception
            );
        }

        return $pull;
    }

    /**
     * Verify if the autoload contains any broken entries.
     *
     * @param   string  $path  The path to look for the autoloader
     *
     * @return  bool  True if there are broken dependencies | False otherwise.
     *
     * @since   4.0.0
     */
    private function verifyAutoloader(string $path): bool
    {
        // Check if we have an autoload file
        if (!file_exists($path . '/libraries/vendor/autoload.php')) {
            return false;
        }

        $composerFiles = [
            'autoload_static.php',
            'autoload_files.php',
            'autoload_classmap.php',
            'autoload_namespaces.php',
            'autoload_psr4.php',
        ];
        $filesToCheck  = [];
        array_walk(
            $composerFiles,
            static function ($composerFile) use (&$filesToCheck, $path) {
                if (file_exists($path . '/libraries/vendor/composer/' . $composerFile) === false) {
                    return;
                }

                if ($composerFile === 'autoload_static.php') {
                    // Get the generated token
                    $autoload    = file_get_contents(
                        $path . '/libraries/vendor/autoload.php'
                    );
                    $resultMatch = preg_match(
                        '/ComposerAutoloaderInit(.*)::/',
                        $autoload,
                        $match
                    );

                    if (!$resultMatch) {
                        return;
                    }

                    // Check if the class is already defined
                    $autoloadClass = '\Composer\Autoload\ComposerStaticInit' . $match[1];

                    if (class_exists($autoloadClass)) {
                        return;
                    }

                    require_once $path . '/libraries/vendor/composer/autoload_static.php';

                    $files        = $autoloadClass::$files;
                    $filesToCheck = array_merge($filesToCheck, $files);
                } else {
                    $files        = require $path
                        . '/libraries/vendor/composer/' . $composerFile;
                    $filesToCheck = array_merge($filesToCheck, $files);
                }
            }
        );

        return $this->checkFilesExist($filesToCheck, $path);
    }

    /**
     * Check a list of files if they exist.
     *
     * @param   array   $files  The list of files to check
     * @param   string  $path   The path where the temporary patch resides
     *
     * @return  bool  True if all files exist | False otherwise.
     *
     * @since   4.0.0
     */
    private function checkFilesExist(array $files, string $path): bool
    {
        $path = Path::clean($path);

        foreach ($files as $file) {
            if (\is_array($file)) {
                $this->checkFilesExist($file, $path);
            } elseif (!file_exists($file)) {
                // Check if the file exists in the Joomla filesystem
                $file = str_ireplace($path, JPATH_SITE, $file);
                if (!file_exists($file)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Saves the applied patch into database
     *
     * @param   int          $id        ID of the applied pull request
     * @param   array        $fileList  List of files
     * @param   string|null  $sha       sha-key from pull request
     *
     * @return  int  last inserted id
     *
     * @throws \Exception
     * @since   3.0
     */
    private function saveAppliedPatch(
        int $id,
        array $fileList,
        ?string $sha = null
    ): int {
        $record = (object)[
            'pull_id'         => $id,
            'data'            => json_encode($fileList),
            'patched_by'      => Factory::getApplication()->getIdentity()->id,
            'applied'         => 1,
            'applied_version' => JVERSION,
        ];
        $db     = $this->getDatabase();
        $db->insertObject('#__patchtester_tests', $record);
        $insertId = $db->insertid();
        if ($sha !== null) {
            // Insert the retrieved commit SHA into the pulls table for this item
            $db->setQuery(
                $db->createQuery()
                    ->update('#__patchtester_pulls')
                    ->set('sha = ' . $db->quote($sha))
                    ->where($db->quoteName('pull_id') . ' = ' . $id)
            )->execute();
        }

        return $insertId;
    }

    /**
     * Patches the code with the supplied pull request
     *
     * @param   int  $id  ID of the pull request to apply
     *
     * @return  bool
     *
     * @throws  \RuntimeException|\Exception
     * @since   2.0
     *
     */
    private function applyWithGitHub(int $id): bool
    {
        $github = Helper::initializeGithub();
        $pull   = $this->retrieveGitHubData($github, $id);
        if ($pull->head->repo === null) {
            throw new \RuntimeException(Text::_('COM_PATCHTESTER_REPO_IS_GONE'));
        }

        try {
            $files = $this->getFiles($id, 1);
        } catch (UnexpectedResponse $exception) {
            throw new \RuntimeException(
                Text::sprintf(
                    'COM_PATCHTESTER_COULD_NOT_CONNECT_TO_GITHUB',
                    $exception->getMessage()
                ),
                $exception->getCode(),
                $exception
            );
        }

        if (!\count($files)) {
            return false;
        }

        $parsedFiles = $this->parseFileList($files);
        if (!\count($parsedFiles)) {
            return false;
        }

        foreach ($parsedFiles as $file) {
            switch ($file->action) {
                case 'deleted':
                    if (!file_exists(JPATH_ROOT . '/' . $file->filename)) {
                        throw new \RuntimeException(
                            Text::sprintf(
                                'COM_PATCHTESTER_FILE_DELETED_DOES_NOT_EXIST_S',
                                $file->filename
                            )
                        );
                    }


                    break;
                case 'added':
                case 'modified':
                case 'renamed':
                    // If the backup file already exists, we can't apply the patch
                    if (file_exists(JPATH_COMPONENT . '/backups/' . md5($file->filename) . '.txt')) {
                        throw new \RuntimeException(
                            Text::sprintf(
                                'COM_PATCHTESTER_CONFLICT_S',
                                $file->filename
                            )
                        );
                    }

                    if (
                        $file->action === 'modified'
                        && !file_exists(JPATH_ROOT . '/' . $file->filename)
                    ) {
                        throw new \RuntimeException(
                            Text::sprintf(
                                'COM_PATCHTESTER_FILE_MODIFIED_DOES_NOT_EXIST_S',
                                $file->filename
                            )
                        );
                    }

                    try {
                        $contentsResponse = $github->getFileContents(
                            $pull->head->user->login,
                            $pull->head->repo->name,
                            $file->repofilename,
                            urlencode($pull->head->ref)
                        );
                        $contents         = json_decode(
                            (string) $contentsResponse->getBody(),
                            false
                        );
                        // In case encoding type ever changes
                        $file->body = match ($contents->encoding) {
                            'base64' => base64_decode($contents->content),
                            default  => throw new \RuntimeException(
                                Text::_(
                                    'COM_PATCHTESTER_ERROR_UNSUPPORTED_ENCODING'
                                )
                            ),
                        };
                    } catch (UnexpectedResponse $exception) {
                        throw new \RuntimeException(
                            Text::sprintf(
                                'COM_PATCHTESTER_COULD_NOT_CONNECT_TO_GITHUB',
                                $exception->getMessage()
                            ),
                            $exception->getCode(),
                            $exception
                        );
                    }

                    break;
            }
        }

        // At this point, we have ensured that we have all the new files and there are no conflicts
        foreach ($parsedFiles as $file) {
            // We only create a backup if the file already exists
            if (
                $file->action === 'deleted'
                || (file_exists(JPATH_ROOT . '/' . $file->filename) && $file->action === 'modified')
                || (file_exists(JPATH_ROOT . '/' . $file->originalFile) && $file->action === 'renamed')
            ) {
                $filename = $file->action === 'renamed' ? $file->originalFile : $file->filename;
                $src      = JPATH_ROOT . '/' . $filename;
                $dest     = JPATH_COMPONENT . '/backups/' . md5($filename) . '.txt';

                if (!File::copy(Path::clean($src), $dest)) {
                    throw new \RuntimeException(
                        Text::sprintf(
                            'COM_PATCHTESTER_ERROR_CANNOT_COPY_FILE',
                            $src,
                            $dest
                        )
                    );
                }
            }

            switch ($file->action) {
                case 'modified':
                case 'added':
                    if (!File::write(Path::clean(JPATH_ROOT . '/' . $file->filename), $file->body)) {
                        throw new \RuntimeException(
                            Text::sprintf(
                                'COM_PATCHTESTER_ERROR_CANNOT_WRITE_FILE',
                                JPATH_ROOT . '/' . $file->filename
                            )
                        );
                    }


                    break;
                case 'deleted':
                    if (!File::delete(Path::clean(JPATH_ROOT . '/' . $file->filename))) {
                        throw new \RuntimeException(
                            Text::sprintf(
                                'COM_PATCHTESTER_ERROR_CANNOT_DELETE_FILE',
                                JPATH_ROOT . '/' . $file->filename
                            )
                        );
                    }


                    break;
                case 'renamed':
                    if (!File::delete(Path::clean(JPATH_ROOT . '/' . $file->originalFile))) {
                        throw new \RuntimeException(
                            Text::sprintf(
                                'COM_PATCHTESTER_ERROR_CANNOT_DELETE_FILE',
                                JPATH_ROOT . '/' . $file->originalFile
                            )
                        );
                    }

                    if (!File::write(Path::clean(JPATH_ROOT . '/' . $file->filename), $file->body)) {
                        throw new \RuntimeException(
                            Text::sprintf(
                                'COM_PATCHTESTER_ERROR_CANNOT_WRITE_FILE',
                                JPATH_ROOT . '/' . $file->filename
                            )
                        );
                    }

                    break;
            }

            // We don't need the file's body any longer (and it causes issues with binary data when json_encode() is run), so remove it
            unset($file->body);
        }

        $this->saveAppliedPatch($pull->number, $parsedFiles, $pull->head->sha);
        // Update the autoloader file
        $this->namespaceMapper->create();
        // Change the media version
        $version = new Version();
        $version->refreshMediaVersion();

        return true;
    }

    /**
     * Get all files from Github.
     *
     * @param   int    $id     The pull ID
     * @param   int    $page   THhe page umber to process
     * @param   array  $files  The list of files retrieved from Github
     *
     * @return  array  LIst of files to process.
     *
     * @since   4.2.0
     */
    private function getFiles(int $id, int $page, array $files = []): array
    {
        $github        = Helper::initializeGithub();
        $filesResponse = $github->getFilesForPullRequest(
            $this->getState()->get('github_user'),
            $this->getState()->get('github_repo'),
            $id,
            $page
        );
        $files         = array_merge($files, json_decode((string) $filesResponse->getBody(), false));
        $lastPage      = 1;
        $headers       = $filesResponse->getHeaders();

        if (!isset($headers['link'])) {
            return $files;
        }

        preg_match(
            '/(\?page=[0-9]{1,3}>; rel=\"last\")/',
            $headers['link'][0],
            $matches
        );
        if ($matches && isset($matches[0])) {
            preg_match('/\d+/', $matches[0], $pages);
            $lastPage = (int)$pages[0];
        }

        if ($page <= $lastPage) {
            $files = $this->getFiles($id, ++$page, $files);
        }

        return $files;
    }

    /**
     * Parse the list of modified files from a pull request
     *
     * @param   array  $files  The modified files to parse
     *
     * @return  array
     *
     * @since   3.0.0
     */
    private function parseFileList(array $files): array
    {
        $parsedFiles = [];
        /*
        * Check if the patch tester is running in a development environment
        * If we are not in development, we'll need to check the exclusion lists
        */
        $isDev = file_exists(JPATH_INSTALLATION . '/index.php');
        foreach ($files as $file) {
            if (!$isDev) {
                $filePath = explode('/', $file->filename);
                if (\in_array($filePath[0], $this->nonProductionFiles, true)) {
                    continue;
                }

                if (\in_array($filePath[0], $this->nonProductionFolders, true)) {
                    continue;
                }
            }

            // Sometimes the repo filename is not the production file name
            $prodFileName        = $file->filename;
            $prodRenamedFileName = $file->previous_filename ?? false;
            $filePath            = explode('/', $prodFileName);
            // Remove the `src` here to match the CMS paths if needed
            if ($filePath[0] === 'src') {
                $prodFileName = preg_replace('/^src\\//', '', $prodFileName, 1);
            }

            if ($prodRenamedFileName) {
                $filePath = explode('/', $prodRenamedFileName);
                // Remove the `src` here to match the CMS paths if needed
                if ($filePath[0] === 'src') {
                    $prodRenamedFileName = preg_replace(
                        '/^src\\//',
                        '',
                        $prodRenamedFileName,
                        1
                    );
                }
            }

            $parsedFiles[] = (object)[
                'action'       => $file->status,
                'filename'     => $prodFileName,
                'repofilename' => $file->filename,
                'fileurl'      => $file->contents_url,
                'originalFile' => $prodRenamedFileName,
            ];
        }

        return $parsedFiles;
    }

    /**
     * Reverts the specified pull request
     * However uses different methods for different repositories.
     *
     * @param   int  $id  ID of the pull request to revert
     *
     * @return  bool
     *
     * @throws  \RuntimeException
     * @since   3.0
     */
    public function revert(int $id): bool
    {
        $params = ComponentHelper::getParams('com_patchtester');
        // Decide based on repository settings whether patch will be applied through Github or CIServer
        if ((bool)$params->get('ci_switch', 0)) {
            return $this->revertWithCIServer($id);
        }

        return $this->revertWithGitHub($id);
    }

    /**
     * Reverts the specified pull request with CIServer options
     *
     * @param   int  $id  ID of the pull request to revert
     *
     * @return  bool
     *
     * @throws  \RuntimeException
     * @since   3.0
     */
    public function revertWithCIServer(int $id): bool
    {
        // Get the CIServer Registry
        $ciSettings = Helper::initializeCISettings();
        $testRecord = $this->getTestRecord($id);
        // We don't want to restore files from an older version
        if ($testRecord->applied_version !== JVERSION) {
            return $this->removeTest($testRecord);
        }

        $files = json_decode($testRecord->data, false);
        if (!$files) {
            throw new \RuntimeException(
                Text::sprintf(
                    'COM_PATCHTESTER_ERROR_READING_DATABASE_TABLE',
                    __METHOD__,
                    htmlentities($testRecord->data)
                )
            );
        }

        $backupsPath = $ciSettings->get('folder.backups')
            . "/$testRecord->pull_id";
        foreach ($files as $file) {
            try {
                $filePath = explode("\\", $file);
                array_pop($filePath);
                $filePath = implode("\\", $filePath);
                // Delete file from root of it exists
                if (file_Exists(JPATH_ROOT . "/$file")) {
                    File::delete(JPATH_ROOT . "/$file");
                    // Move from backup, if it exists there
                    if (file_exists($backupsPath . '/' . $file)) {
                        File::move(
                            $backupsPath . '/' . $file,
                            JPATH_ROOT . '/' . $file
                        );
                    }

                    // If folder is empty, remove it as well
                    if (\count(glob(JPATH_ROOT . '/' . $filePath . '/*')) === 0) {
                        Folder::delete(JPATH_ROOT . '/' . $filePath);
                    }
                } elseif (file_exists($backupsPath . '/' . $file)) {
                    // Move from backup, if file exists there - got deleted by patch
                    if (!file_exists(JPATH_ROOT . '/' . $filePath)) {
                        Folder::create(JPATH_ROOT . '/' . $filePath);
                    }

                    File::move(
                        $backupsPath . '/' . $file,
                        JPATH_ROOT . '/' . $file
                    );
                }
            } catch (\RuntimeException $exception) {
                throw new \RuntimeException(
                    Text::sprintf(
                        'COM_PATCHTESTER_FAILED_REVERT_PATCH',
                        $file,
                        $exception->getMessage()
                    )
                );
            }
        }

        Folder::delete($backupsPath);
        // Update the autoloader file
        $this->namespaceMapper->create();
        // Change the media version
        $version = new Version();
        $version->refreshMediaVersion();

        return $this->removeTest($testRecord);
    }

    /**
     * Retrieves test data from database by specific id
     *
     * @param   int  $id  ID of the record
     *
     * @return  \stdClass  The record looking for
     *
     * @since   3.0.0
     */
    private function getTestRecord(int $id): \stdClass
    {
        $db = $this->getDatabase();

        return $db->setQuery(
            $db->createQuery()
                ->select('*')
                ->from('#__patchtester_tests')
                ->where('id = ' . $id)
        )->loadObject();
    }

    /**
     * Remove the database record for a test
     *
     * @param   \stdClass  $testRecord  The record being deleted
     *
     * @return  bool
     *
     * @since   3.0.0
     */
    private function removeTest(\stdClass $testRecord): bool
    {
        $db = $this->getDatabase();
        // Remove the retrieved commit SHA from the pulls table for this item
        $db->setQuery(
            $db->createQuery()
                ->update('#__patchtester_pulls')
                ->set('sha = ' . $db->quote(''))
                ->where($db->quoteName('id') . ' = ' . (int)$testRecord->id)
        )->execute();
        // And delete the record from the tests table
        $db->setQuery(
            $db->createQuery()
                ->delete('#__patchtester_tests')
                ->where('id = ' . (int)$testRecord->id)
        )->execute();

        return true;
    }

    /**
     * Reverts the specified pull request with Github Requests
     *
     * @param   int  $id  ID of the pull request to revert
     *
     * @return  bool
     *
     * @throws  \RuntimeException
     * @since   2.0
     */
    public function revertWithGitHub(int $id): bool
    {
        $testRecord = $this->getTestRecord($id);
        // We don't want to restore files from an older version
        if ($testRecord->applied_version != JVERSION) {
            return $this->removeTest($testRecord);
        }

        $files = json_decode($testRecord->data, false);
        if (!$files) {
            throw new \RuntimeException(
                Text::sprintf(
                    'COM_PATCHTESTER_ERROR_READING_DATABASE_TABLE',
                    __METHOD__,
                    htmlentities($testRecord->data)
                )
            );
        }

        foreach ($files as $file) {
            switch ($file->action) {
                case 'deleted':
                case 'modified':
                    $src  = JPATH_COMPONENT . '/backups/' . md5($file->filename)
                        . '.txt';
                    $dest = JPATH_ROOT . '/' . $file->filename;
                    if (!File::copy($src, $dest)) {
                        throw new \RuntimeException(
                            Text::sprintf(
                                'COM_PATCHTESTER_ERROR_CANNOT_COPY_FILE',
                                $src,
                                $dest
                            )
                        );
                    }

                    if (file_exists($src)) {
                        if (!File::delete($src)) {
                            throw new \RuntimeException(
                                Text::sprintf(
                                    'COM_PATCHTESTER_ERROR_CANNOT_DELETE_FILE',
                                    $src
                                )
                            );
                        }
                    }


                    break;
                case 'added':
                    $src = JPATH_ROOT . '/' . $file->filename;
                    if (file_exists($src)) {
                        if (!File::delete($src)) {
                            throw new \RuntimeException(
                                Text::sprintf(
                                    'COM_PATCHTESTER_ERROR_CANNOT_DELETE_FILE',
                                    $src
                                )
                            );
                        }
                    }

                    break;
                case 'renamed':
                    $originalSrc = JPATH_COMPONENT . '/backups/' . md5($file->originalFile) . '.txt';
                    $newSrc      = JPATH_ROOT . '/' . $file->filename;
                    $dest        = JPATH_ROOT . '/' . $file->originalFile;

                    if (!File::copy($originalSrc, $dest)) {
                        throw new \RuntimeException(
                            Text::sprintf(
                                'COM_PATCHTESTER_ERROR_CANNOT_COPY_FILE',
                                $originalSrc,
                                $dest
                            )
                        );
                    }

                    if (file_exists($originalSrc)) {
                        if (!File::delete($originalSrc)) {
                            throw new \RuntimeException(
                                Text::sprintf(
                                    'COM_PATCHTESTER_ERROR_CANNOT_DELETE_FILE',
                                    $originalSrc
                                )
                            );
                        }
                    }

                    if (file_exists($newSrc)) {
                        if (!File::delete($newSrc)) {
                            throw new \RuntimeException(
                                Text::sprintf(
                                    'COM_PATCHTESTER_ERROR_CANNOT_DELETE_FILE',
                                    $newSrc
                                )
                            );
                        }
                    }


                    break;
            }
        }

        // Update the autoloader file
        $this->namespaceMapper->create();
        // Change the media version
        $version = new Version();
        $version->refreshMediaVersion();

        return $this->removeTest($testRecord);
    }
}
