<?php

namespace Melihovv\LaravelLogViewer;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Class LaravelLogViewer
 * ----
 * The class for registering the facade name form the package
 * 
 * @author  Tim Joosten        <https://github.com/tjoosten>
 * @author  Alexander Melihovv <https://github.com/melihovv>
 * @license MIT License        <https://github.com/Misfits-BE/laravel-log-viewer/blob/master/LICENSE>
 */
class LaravelLogViewer
{
    /**
     * Base directory.
     *
     * @var string
     */
    protected $baseDir;

    /**
     * Current directory.
     *
     * @var string
     */
    protected $currentDir;

    /** @var string $currentFile Current file */
    protected $currentFile;

    /** @var int $maxFileSize Max File size. */
    protected $maxFileSize;

    protected static $levelsClasses = [
        'debug' => 'info',
        'info' => 'info',
        'notice' => 'info',
        'warning' => 'warning',
        'error' => 'danger',
        'critical' => 'danger',
        'alert' => 'danger',
        'emergency' => 'danger',
    ];

    protected static $levelsImgs = [
        'debug' => 'info',
        'info' => 'info',
        'notice' => 'info',
        'warning' => 'warning',
        'error' => 'warning',
        'critical' => 'warning',
        'alert' => 'warning',
        'emergency' => 'warning',
    ];

    /**
     * LaravelLogViewer constructor 
     * 
     * @param string $baseDir
     * @param int    $maxFileSize
     */
    public function __construct($baseDir, $maxFileSize)
    {
        $this->baseDir     = realpath($baseDir);
        $this->currentDir  = $this->baseDir;
        $this->maxFileSize = $maxFileSize;
    }

    /**
     * Returns logs from current file.
     *
     * @return array|null
     */
    public function getLogsFromCurrentFile()
    {
        if ($this->currentFile === null) {
            return [];
        }

        if (File::size($this->currentFile) > $this->maxFileSize) {
            return;
        }

        $datePattern = '\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}';
        $pattern = "/\\[$datePattern\\].*/";
        $fileContent = File::get($this->currentFile);

        preg_match_all($pattern, $fileContent, $rows);

        if (!is_array($rows) || count($rows) === 0) {
            return [];
        }

        $rows = $rows[0];
        $logs = [];

        foreach ($rows as $row) {
            preg_match(
                "/^\\[($datePattern)\\].*?(\\w+)\\."
                . '([A-Z]+): (.*?)( in .*?:[0-9]+)?$/',
                $row,
                $matches
            );

            if (!isset($matches[4])) {
                continue;
            }

            $level = Str::lower($matches[3]);

            $inFile = null;
            if (isset($matches[5])) {
                $inFile = substr($matches[5], 4);
            }

            $logs[] = (object) [
                'context' => $matches[2],
                'level' => $level,
                'levelClass' => static::$levelsClasses[$level],
                'levelImg' => static::$levelsImgs[$level],
                'date' => $matches[1],
                'text' => trim($matches[4]),
                'inFile' => $inFile,
            ];
        }

        return array_reverse($logs);
    }

    /**
     * Returns content (files and folders) of current folder.
     *
     * @return array
     */
    public function getCurrentDirectoryContent(): array
    {
        $content = File::glob($this->currentDir . DIRECTORY_SEPARATOR . '*');

        $content = array_map(function ($item): object {
            return (object) [
                'path'   => $this->getPathRelativeToBaseDir($item),
                'name'   => Str::substr($item, Str::length($this->currentDir) + 1),
                'isFile' => File::isFile($item),
                'isDir'  => File::isDirectory($item),
            ];
        }, $content);

        return $content;
    }

    /**
     * Get the base directory in the log viewer. 
     * 
     * @return string
     */
    public function getBaseDirectory(): string
    {
        return $this->baseDir;
    }

    /**
     * @param  string $baseDir
     * @return object $this
     */
    public function setBaseDirectory(string $baseDir): object
    {
        $this->baseDir = $baseDir;

        return $this;
    }

    /**
     * Absolute path to current directory.
     *
     * @return string
     */
    public function getCurrentDirectory(): string
    {
        return $this->currentDir;
    }

    /**
     * Get the current directory that is relative to the base directory.
     * 
     * @return string
     */
    public function getCurrentDirectoryRelativeToBaseDir(): string
    {
        return $this->getPathRelativeToBaseDir($this->currentDir);
    }

    /**
     * Set the current directory in the log viewer.
     *
     * @throws InvalidArgumentException
     *
     * @param  string $directory Relative path to directory from base path.
     * @return object $this
     */
    public function setCurrentDirectory($directory): object
    {
        $directory = $this->normalizePath("$this->baseDir/$directory");

        $this->checkIfPathInBaseDir($directory);
        $this->currentDir = $directory;

        return $this;
    }

    /**
     * Get the current file in the log viewer. 
     * 
     * @return string
     */
    public function getCurrentFile(): string
    {
        return $this->currentFile;
    }

    /**
     * Get the current file that is relative to the base directory. 
     * 
     * @return string
     */
    public function getCurrentFileRelativeToBaseDir(): string
    {
        return $this->getPathRelativeToBaseDir($this->currentFile);
    }

    /**
     * Returns relative path to base directory.
     *
     * @param  string $path Absolute path.
     * @return string
     */
    protected function getPathRelativeToBaseDir(string $path): string
    {
        return Str::substr($path, Str::length($this->baseDir));
    }

    /**
     * Set the current file in the log viewer. 
     * 
     * @throws InvalidArgumentException
     *
     * @param  string $file Relative path to file from base directory.
     * @return object $this
     */
    public function setCurrentFile(string $file): object
    {
        $file = $this->normalizePath("$this->baseDir/$file");

        $this->checkIfPathInBaseDir($file);
        $this->currentFile = $file;

        $dir = File::dirname($file);
        $this->currentDir = $dir;

        return $this;
    }

    /**
     * Checks if passed path is inside base directory.
     *
     * @throws InvalidArgumentException
     * 
     * @param  string $path Absolute path.
     * @return void
     */
    protected function checkIfPathInBaseDir(string $path): void
    {
        if (! Str::startsWith($path, $this->baseDir)) {
            throw new InvalidArgumentException(
                "Passed directory is not in base directory $this->baseDir"
            );
        }
    }

    /**
     * Normalizes path.
     *
     * @throws InvalidArgumentException
     *
     * @param  string $path Absolute path.
     * @return string Normalized path.
     */
    protected function normalizePath(string $path): string
    {
        $path = realpath($path);

        if ($path === false) {
            throw new InvalidArgumentException('Not existing path');
        }

        return $path;
    }

    /**
     * Returns path to parent of current directory.
     *
     * @return string
     */
    public function getRelativePathToCurrentDirectoryParent(): string
    {
        if ($this->baseDir === $this->currentDir) {
            return DIRECTORY_SEPARATOR;
        }

        $path = realpath($this->currentDir . DIRECTORY_SEPARATOR . '..');
        
        return $this->getPathRelativeToBaseDir($path) ?: DIRECTORY_SEPARATOR;
    }

    /**
     * Returns true if current directory is also base directory.
     *
     * @return bool
     */
    public function isCurrentDirectoryBase(): bool
    {
        return $this->currentDir === $this->baseDir;
    }

    /**
     * Returns parent directories of current directory.
     *
     * @return array
     */
    public function getParentDirectories(): array
    {
        if ($this->isCurrentDirectoryBase()) {
            return [];
        }

        $currentDir = $this->currentDir;
        $dirs       = [];

        do {
            $dir        = dirname($currentDir);
            $dirs[]     = $this->getPathRelativeToBaseDir($dir) ?: DIRECTORY_SEPARATOR;
            $currentDir = $dir;
        } while ($dir !== $this->baseDir);

        return array_reverse($dirs);
    }
}
