<?php

declare(strict_types=1);

namespace Akeneo\PimMigration\Domain;

use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem as SymfonyFilesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;

/**
 * FileSystem Helper.
 *
 * @author    Anael Chardan <anael.chardan@akeneo.com>
 * @copyright 2017 Akeneo SAS (http://www.akeneo.com)
 */
class FileSystemHelper
{
    /**
     * @throws \InvalidArgumentException
     */
    public function getRealPath(string $path): string
    {
        $result = realpath($path);

        if (false === $result) {
            throw new \InvalidArgumentException('The path you give is not a real one');
        }

        return $result;
    }

    public function fileExists(string $path): bool
    {
        return $this->getFileSystem()->exists($path);
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function getFileContent(string $path): string
    {
        $result = file_get_contents($path);

        if (false === $result) {
            throw new \InvalidArgumentException(
                sprintf('The path "%s" is wrong or unreadable', $path)
            );
        }

        return $result;
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function getFileLines(string $path): array
    {
        $result = file($path);

        if (false === $result) {
            throw new \InvalidArgumentException(sprintf('The path "%s" is wrong or the file is unreadable', $path));
        }

        return $result;
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function getFileLine(string $path, int $line): string
    {
        $lines = $this->getFileLines($path);

        if (count($lines) < $line) {
            throw new \InvalidArgumentException(sprintf(
                'Line %d does not exist in the "%s" file. The file contains only %s lines',
                $line,
                $path,
                count($lines)
            ));
        }

        return $lines[$line - 1];
    }

    public function updateLineInFile(string $path, int $line, string $content): void
    {
        $lines = $this->getFileLines($path);
        $lines[$line - 1] = $content;

        $this->getFileSystem()->dumpFile($path, implode('', $lines));
    }

    public function copyFile(string $from, string $to, bool $overwrite): void
    {
        $this->getFileSystem()->copy($from, $to, $overwrite);
    }

    public function dumpFile(string $path, string $content): void
    {
        $this->getFileSystem()->dumpFile($path, $content);
    }

    public function getYamlContent(string $path): array
    {
        return Yaml::parse($this->getFileContent($path));
    }

    public function dumpYamlInFile(string $path, array $content): void
    {
        $content = Yaml::dump($content, 4);

        $this->dumpFile($path, $content);
    }

    public function remove(Finder $files): void
    {
        $fileSystem = $this->getFileSystem();

        try {
            $fileSystem->remove($files);
        } catch (IOException $io) {
            throw new \InvalidArgumentException(sprintf("Unable to delete the following path: '%s'", $io->getPath()));
        }
    }

    /**
     * @see https://stackoverflow.com/a/27290570
     */
    public function copyDirectory(string $from, string $to): void
    {
        $fileSystem = $this->getFileSystem();

        if (file_exists($to)) {
            $fileSystem->remove($to);
        }

        $fileSystem->mkdir($to);

        $directoryIterator = new \RecursiveDirectoryIterator($from, \RecursiveDirectoryIterator::SKIP_DOTS);
        $iterator = new \RecursiveIteratorIterator($directoryIterator, \RecursiveIteratorIterator::SELF_FIRST);
        foreach ($iterator as $item) {
            if ($item->isDir()) {
                $fileSystem->mkdir($to.DIRECTORY_SEPARATOR.$iterator->getSubPathName());
            } else {
                $fileSystem->copy($item, $to.DIRECTORY_SEPARATOR.$iterator->getSubPathName());
            }
        }
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function extractArchive(string $archivePath, string $to): void
    {
        if (false === $this->getFileSystem()->isAbsolutePath($archivePath)) {
            throw new \InvalidArgumentException(sprintf('Archive "%s" has to be an absolute path', $archivePath));
        }

        if (false === $this->getFileSystem()->isAbsolutePath($to)) {
            throw new \InvalidArgumentException(sprintf('The destination "%s" should be an absolute path', $to));
        }

        $archive = new \PharData($archivePath);

        $archive->extractTo($to, null, true);
    }

    /**
     * Writes a import CSV file. The file will be emptied if it already exists.
     */
    public function writeImportFile(array $data, string $filePath): void
    {
        $fileHandle = fopen($filePath, 'w');
        if (!is_resource($fileHandle)) {
            throw new \InvalidArgumentException('Unable to open the file '.$filePath);
        }

        if (empty($data)) {
            fclose($fileHandle);

            return;
        }

        fputcsv($fileHandle, array_keys($data[0]), ';');

        foreach ($data as $dataLine) {
            fputcsv($fileHandle, $dataLine, ';');
        }

        fclose($fileHandle);
    }

    private function getFileSystem(): SymfonyFilesystem
    {
        return new SymfonyFilesystem();
    }
}
