<?php

namespace CodeDistortion\LaravelAutoReg\Support;

use CodeDistortion\LaravelAutoReg\Exceptions\FilesystemException;
use ErrorException;
use Illuminate\Support\Collection;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use UnexpectedValueException;

/**
 * Filesystem utilities.
 */
class FileSystem
{
    /**
     * Find files recursively within the given directory and resolve their FQCN (when they contain classes).
     *
     * @param string $dir The directory to look through.
     * @return Collection|string[]
     * @throws FilesystemException When there was a problem reading from the filesystem.
     */
    public static function getRecursiveFileFQCNList(string $dir): Collection
    {
        try {
            return static::getRecursiveFileList($dir)
                ->mapWithKeys(fn($path) => [$path => FileSystem::getFileFQCN($path)]);
        } catch (UnexpectedValueException $e) {
            throw !file_exists($dir) || !is_dir($dir)
                ? FilesystemException::sourceDirNotFound($dir, $e)
                : FilesystemException::errorReadingFromLookInDir($dir, $e);
        }
    }

    /**
     * Find files recursively within the given directory.
     *
     * @param string $dir The directory to look through.
     * @return Collection|string[]
     */
    private static function getRecursiveFileList(string $dir): Collection
    {
        return collect(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir)))
            ->filter(fn($path) => $path->isFile())
            ->map(
                fn($path) => str_replace('\\', '/', (string) realpath($path->getPathName()))
            )
            ->sort()
            ->values();
    }

    /**
     * Analyse the given file and determine its FQCN (if it's a class).
     *
     * Slightly adapted from: https://stackoverflow.com/a/7153391 .
     *
     * @param string $path The file to look at.
     * @return string|null
     */
    private static function getFileFQCN(string $path): ?string
    {
        // T_NAME_QUALIFIED was introduced in PHP8 - https://wiki.php.net/rfc/namespaced_names_as_token
        $namespaceConsts = defined('T_NAME_QUALIFIED')
            ? [T_STRING, T_NAME_QUALIFIED]
            : [T_STRING];

        $fp = null;
        try {

            $class = $namespace = null;
            $buffer = '';
            $i = 0;

            $fp = fopen($path, 'r');
            if (!$fp) {
                return null;
            }

            while (!$class) {

                if (feof($fp)) {
                    break;
                }

                // if the whole file isn't read, a PHP Warning might be raised if it breaks in a strange place
                // (like within a comment - leaving it un-terminated)
                do {
                    $buffer .= fread($fp, 1024 * 100);
                } while (!feof($fp));

                $tokens = token_get_all($buffer);

                for (; $i < count($tokens); $i++) {
                    if ($tokens[$i][0] === T_NAMESPACE) {
                        for ($j = $i + 1; $j < count($tokens); $j++) {

                            // [T_STRING, T_NAME_QUALIFIED]
                            if (in_array($tokens[$j][0], $namespaceConsts, true)) {
                                $namespace .= '\\' . $tokens[$j][1];
                            } elseif ($tokens[$j] === '{' || $tokens[$j] === ';') {
                                break;
                            }
                        }
                    }

                    if (!mb_strlen($class)) {
                        if ($tokens[$i][0] === T_CLASS) {
                            if (@$tokens[$i - 1][0] !== T_PAAMAYIM_NEKUDOTAYIM) { // not part of "XYZ::class"
                                for ($j = $i + 1; $j < count($tokens); $j++) {
                                    if ($tokens[$j] === '{') {
                                        $class = $tokens[$i + 2][1];
                                        break;
                                    }
                                }
                            }
                        }
                    }
                }
            }

            if ($class) {
                return $namespace ? "$namespace\\$class" : $class;
            }
            return null;

        } catch (ErrorException $e) {
            return null;
        } finally {
            if ($fp) {
                fclose($fp);
            }
        }
    }
}
