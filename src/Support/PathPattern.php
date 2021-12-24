<?php

namespace CodeDistortion\LaravelAutoReg\Support;

use CodeDistortion\LaravelAutoReg\Exceptions\PathPatternException;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Represents a path-pattern and subsequent regexes used to match paths from the filesystem.
 */
class PathPattern
{
    /** @var string The directory part of the pattern. */
    private string $dir;

    /** @var string The directory part of the pattern - before any "*" characters. */
    private string $dirBeforeStar;

    /** @var string The filename - before the extension. */
    private string $baseFilename;

    /** @var string The filename extension - including the ".". */
    private string $extension;



    /** @var string|null The regex that the file's path must match to be valid. */
    protected ?string $matchPathRegex;

    /** @var string|null The regex that the file's class FQCN must match to be valid. */
    protected ?string $matchFqcnRegex;

    /** @var array<int, string|null> The classes that files must match when not empty (null means "no class"). */
    protected array $matchClasses = [null];

    /** @var string|null The regex used to pre-process the file's path. */
    protected ?string $pickFullPathRegex;

    /** @var string|null The regex used to pick the App from the path. */
    protected ?string $pickAppRegex;

    /** @var string|null The regex used to pick the file's "name" from the path. */
    protected ?string $pickNameRegex;

    /** @var string|null The regex used to pick the wildcard and the rest */
    protected ?string $pickStarsAndAfterRegex;


    /**
     * @param string $pattern The path-pattern to use.
     * @throws PathPatternException Thrown when a search-pattern is invalid.
     */
    public function __construct(string $pattern)
    {
        $this->setPattern($pattern);
    }

    /**
     * Initialise this object with a new path-pattern.
     *
     * @param string $pattern The pattern to use.
     * @return $this
     * @throws PathPatternException Thrown when a search-pattern is invalid.
     */
    public function setPattern(string $pattern): self
    {
        $pattern = str_replace('\\', '/', $pattern);
        if (!preg_match("/^(\/?.+\/)?(?:(.+)(\.[^.]+))?$/", $pattern, $matches)) {
            throw PathPatternException::invalidPattern($pattern);
        }

        $dir = $this->prepDir($matches[1]);
        $dirBeforeStar = $this->prepDir(Str::before($matches[1], '*'));
        $baseFilename = $this->prepBaseFilename($matches[2]);
        $extension = $this->prepFileExtension($matches[3]);

        $this->dir = $dir;
        $this->dirBeforeStar = $dirBeforeStar;
        $this->baseFilename = $baseFilename;
        $this->extension = $extension;

        // these are the default "match" regexes - used when detecting if a file is relevant
        $this->matchPathRegex = $dir
            ? "/^.*\/{$dir}\/{$baseFilename}{$extension}$/"
            : "/^.*\/{$baseFilename}{$extension}$/";
        $this->matchFqcnRegex = null;
        $this->matchClasses = [null];

        // these are the default regexes used to "pick" values from paths
        $this->pickFullPathRegex = null;
        $this->pickAppRegex = $dir
            ? "/^(.*)\/{$dir}\/{$baseFilename}{$extension}$/"
            : "/^(.*)\/{$baseFilename}{$extension}$/";
        $this->pickNameRegex = $dir
            ? "/^.*\/{$dir}\/({$baseFilename}){$extension}$/"
            : "/^.*\/({$baseFilename}){$extension}$/";
        $this->pickStarsAndAfterRegex = $dirBeforeStar
            ? "/^.*\/{$dirBeforeStar}\/(.*)$/"
            : "/^.*\/(.*)$/";
        $this->pickStarsAndAfterRegex = $dirBeforeStar
            ? "/^.*\/{$dirBeforeStar}\/(.*)$/"
            : "/^.*\/(.*)$/";

        return $this;
    }


    /**
     * Get the dir regex-part.
     *
     * @return string
     */
    public function getDirRegexPart(): string
    {
        return $this->dir;
    }

    /**
     * Get the dir-before-star regex-part.
     *
     * @return string
     */
    public function getDirBeforeStarRegexPart(): string
    {
        return $this->dirBeforeStar;
    }

//    /**
//     * Get the base-filename regex-part.
//     *
//     * @return string
//     */
//    public function getBaseFilenameRegexPart(): string
//    {
//        return $this->baseFilename;
//    }

//    /**
//     * Get the filename-extension regex-part.
//     *
//     * @return string
//     */
//    public function getExtensionRegexPart(): string
//    {
//        return $this->extension;
//    }



//    /**
//     * Set the match-path regex.
//     *
//     * @param string|null $matchPathRegex The regex to use.
//     * @return void
//     */
//    public function setMatchPathRegex(?string $matchPathRegex): void
//    {
//        $this->matchPathRegex = $matchPathRegex;
//    }

//    /**
//     * Set the match-FQCN regex.
//     *
//     * @param string|null $matchFqcnRegex The regex to use.
//     * @return void
//     */
//    public function setMatchFqcnRegex(?string $matchFqcnRegex): void
//    {
//        $this->matchFqcnRegex = $matchFqcnRegex;
//    }

    /**
     * Set the classes to match.
     *
     * @param array<int, string|null> $matchClasses The classes to use.
     * @return void
     */
    public function setMatchClasses(array $matchClasses): void
    {
        $this->matchClasses = $matchClasses;
    }

    /**
     * Set the full-path regex.
     *
     * @param string|null $pickFullPathRegex The regex to use.
     * @return void
     */
    public function setPickFullPathRegex(?string $pickFullPathRegex): void
    {
        $this->pickFullPathRegex = $pickFullPathRegex;
    }

    /**
     * Set the pick-app regex.
     *
     * @param string|null $pickAppRegex The regex to use.
     * @return void
     */
    public function setPickAppRegex(?string $pickAppRegex): void
    {
        $this->pickAppRegex = $pickAppRegex;
    }

    /**
     * Set the pick-name regex.
     *
     * @param string|null $pickNameRegex The regex to use.
     * @return void
     */
    public function setPickNameRegex(?string $pickNameRegex): void
    {
        $this->pickNameRegex = $pickNameRegex;
    }

//    /**
//     * Set the pick-stars-and-after regex.
//     *
//     * @param string|null $pickStarsAndAfterRegex The regex to use.
//     * @return void
//     */
//    public function setPickStarsAndAfterRegex(?string $pickStarsAndAfterRegex): void
//    {
//        $this->pickStarsAndAfterRegex = $pickStarsAndAfterRegex;
//    }



    /**
     * Process a directory ready for use in regexes.
     *
     * @param string $dir The directory to treat.
     * @return string
     */
    private function prepDir(string $dir): string
    {
        $dir = preg_replace('/[*][*]+/', '**', $dir);
        $dir = preg_quote((string) $dir, '/');
        $dir = str_replace("\/\\*\\*", "(?:\/[^\/]+)*", $dir);
        $dir = str_replace("\\*", "[^\/]+", $dir);
        if (Str::startswith($dir, '\/')) {
            $dir = Str::replacefirst('\/', '', $dir);
        }
        if (Str::endswith($dir, '\/')) {
            $dir = Str::replaceLast('\/', '', $dir);
        }
        return $dir;
    }

    /**
     * Process a base-filename extension ready for use in regexes.
     *
     * @param string $baseFilename The filename-part to treat.
     * @return string
     */
    private function prepBaseFilename(string $baseFilename): string
    {
        $baseFilename = preg_quote($baseFilename, '/');
        $baseFilename = str_replace('\\*', "[^\/]+", $baseFilename);
        return $baseFilename;
    }

    /**
     * Process a filename extension ready for use in regexes.
     *
     * @param string $extension The extension to treat.
     * @return string
     */
    private function prepFileExtension(string $extension): string
    {
        $extension = preg_quote($extension, '/');
        $extension = str_replace('\\*', "[^\.\/]+", $extension);
        return $extension;
    }



    /**
     * Remove irrelevant files from the list.
     *
     * @param string                 $sourceDir The directory the files were found in.
     * @param Collection|BasicFile[] $paths     The list of files.
     * @return Collection|BasicFile[]
     */
    public function pickRelevantFiles(string $sourceDir, Collection $paths): Collection
    {
        return $paths->filter(
            fn($basicFile) => $this->isRelevantFile($sourceDir, $basicFile->localPath, $basicFile->fqcn)
        );
    }

    /**
     * Condense the list of files if needed (e.g. remove the filenames and pick out the directory for views).
     *
     * @param Collection|BasicFile[] $paths The list of files.
     * @return Collection<array<BasicFile[]>>
     */
    public function groupByCondensedPath(Collection $paths): Collection
    {
        $grouped = [];
        foreach ($paths as $basicFile) {
            $condensedPath = $this->pickFullPath($basicFile->localPath);

            $grouped[$condensedPath] ??= collect();
            $grouped[$condensedPath][] = $basicFile;
        }
        return collect($grouped);
    }

    /**
     * Condense the list of files if needed (e.g. remove the filenames and pick out the directory for views).
     *
     * @param Collection|BasicFile[] $paths The list of files.
     * @return Collection|BasicFile[]
     */
    public function condenseFiles(Collection $paths): Collection
    {
        return $paths
            ->map(
                fn(BasicFile $basicFile) => new BasicFile(
                    $this->pickFullPath($basicFile->path),
                    $this->pickFullPath($basicFile->localPath),
                    $this->pickFullPath($basicFile->appPath),
                    null,
                    $this->pickFqcn($basicFile->localPath, $basicFile->fqcn)
                )
            )
            ->unique('path');
    }

    /**
     * Check if the given file is relevant.
     *
     * @param string      $sourceDir The directory this file was found in.
     * @param string      $localPath The path to the file within the project.
     * @param string|null $fqcn      The class's FQCN (when available).
     * @return boolean
     */
    private function isRelevantFile(string $sourceDir, string $localPath, ?string $fqcn): bool
    {
        $appPath = Str::replaceFirst($sourceDir, '', $localPath);

        if (!$this->matchPath($appPath)) {
            return false;
        }

        if (!$this->matchFqcn($fqcn)) {
            return false;
        }

        if (!$this->matchClass($fqcn)) {
            return false;
        }

        return true;
    }

    /**
     * Check if a path matches the matchPathRegex.
     *
     * @param string $appPath The path to check.
     * @return boolean
     */
    private function matchPath(string $appPath): bool
    {
        if (!$this->matchPathRegex) {
            return true;
        }

        if (!preg_match($this->matchPathRegex, $appPath)) {
            return false;
        }

        return true;
    }

    /**
     * Check if a class FQCN matches the matchFqcnRegex.
     *
     * @param string|null $fqcn The class FQCN to check.
     * @return boolean
     */
    private function matchFqcn(?string $fqcn): bool
    {
        if (!$this->matchFqcnRegex) {
            return true;
        }

        if (!preg_match($this->matchFqcnRegex, (string) $fqcn)) {
            return false;
        }

        return true;
    }

    /**
     * Check if a class FQCN is in the list of valid classes.
     *
     * @param string|null $fqcn The class FQCN to check.
     * @return boolean
     */
    private function matchClass(?string $fqcn): bool
    {
        if (!count($this->matchClasses)) {
            return true;
        }

        if (!$this->inClassList($fqcn, $this->matchClasses)) {
            return false;
        }

        return true;
    }

    /**
     * Check if the given class is in a set of classes.
     *
     * @param string|null             $fqcn         The class to check.
     * @param array<int, string|null> $allowedFqcns The possible classes.
     * @return boolean
     */
    private function inClassList(?string $fqcn, array $allowedFqcns): bool
    {
        foreach ($allowedFqcns as $matchFqcn) {
            // no class
            if (is_null($matchFqcn)) {
                if (is_null($fqcn)) {
                    return true;
                }
            // class
            } elseif (is_a((string) $fqcn, $matchFqcn, true)) {
                return true;
            }
        }
        return false;
    }



    /**
     * Pull the relevant part out of the local-path when needed.
     *
     * @param string $localPath The path to match against.
     * @return string
     */
    private function pickFullPath(string $localPath): string
    {
        if (!$this->pickFullPathRegex) {
            return $localPath;
        }

        if (!preg_match($this->pickFullPathRegex, $localPath, $matches)) {
            return $localPath;
        }

        array_shift($matches);
        return rtrim(implode('/', $matches), '/');
    }

    /**
     * The "pickFullPath" method may remove part of the end of the directory structure.
     * This also removes that from the FQCN.
     *
     * @param string      $path The path that the class exists in.
     * @param string|null $fqcn The class FQCN.
     * @return string|null
     */
    private function pickFqcn(string $path, ?string $fqcn): ?string
    {
        if (is_null($fqcn)) {
            return null;
        }

        $treatedPath = $this->pickFullPath($path);
        if ($treatedPath == $path) {
            return $fqcn;
        }

        // remove the filename, remove its extension, and put it back
        $dirParts = Str::of($path)->after($treatedPath)->explode('/');
        $last = Str::of($dirParts->pop())->explode('.');
        if ($last->count() > 1) {
            $last->pop();
        }
        $dirParts->push($last->implode('.'));
        $removedNamespace = $dirParts->implode('\\');

        $fqcn = Str::of($fqcn);
        return $fqcn->endsWith($removedNamespace)
            ? $fqcn->replaceLast($removedNamespace, '')
            : $fqcn;
    }



    /**
     * Pick out the app's name from the path within the app.
     *
     * @param string $appPath The path to the file, within the "app".
     * @return string|null
     */
    public function pickAppName(string $appPath): ?string
    {
        return $this->regexPick($appPath, $this->pickAppRegex);
    }

    /**
     * Pick out the name from the path within the app.
     *
     * @param string $appPath The path to the file, within the "app".
     * @return string|null
     */
    public function pickName(string $appPath): ?string
    {
        return $this->regexPick($appPath, $this->pickNameRegex);
    }

    /**
     * Pick the after-star-directory part from a path.
     *
     * @param string $appPath The path to the file, within the "app".
     * @return string|null
     */
    public function pickAfterStarDir(string $appPath): ?string
    {
        if (
            preg_match(
                "/^.+\/{$this->dirBeforeStar}\/(?:(.+)\/)?{$this->baseFilename}{$this->extension}$/",
                $appPath,
                $matches
            )
        ) {
            return $matches[1] ?? null;
        }
        return null;
    }

    /**
     * Pick the wildcard "**" part and after from a path.
     *
     * @param string $appPath The path to the file, within the "app".
     * @return string|null
     */
    public function pickStarsAndAfterPath(string $appPath): ?string
    {
        preg_match((string) $this->pickStarsAndAfterRegex, $appPath, $matches);
        return $matches[1];
    }



    /**
     * Pick the matched regex values.
     *
     * @param string      $value The value to inspect.
     * @param string|null $regex The regex to run against.
     * @return string|null
     */
    private function regexPick(string $value, ?string $regex): ?string
    {
        if (!$regex) {
            return null;
        }

        if (!preg_match($regex, $value, $matches)) {
            return null;
        }

        array_shift($matches);
        $temp = ltrim(implode('/', $matches), '/');
        $temp = explode('/', $temp);
        $temp = collect($temp)->map(fn($item) => Str::snake($item))->implode('.');
        return mb_strlen($temp) ? $temp : null;
    }



//    /**
//     * Try to identify the app from the list of apps that have already been identified.
//     *
//     * @param string[] $detectedApps The apps that have been detected.
//     * @param string   $appPath      The path to the file.
//     * @return string|null
//     */
//    private function pickFallbackApp(array $detectedApps, string $appPath): ?string
//    {
//        if (!count($detectedApps)) {
//            return null;
//        }
//
//        $fallbackAppRegex = "/^(.+)\/.+\.php$/";
//
//        $app = $this->regexPick($appPath, $fallbackAppRegex);
//        if (!$app) {
//            return null;
//        }
//
//        // check to see if the app is within an app that has already been identified
//        foreach ($detectedApps as $detectedApp) {
//            if (mb_strpos($app.'.', $detectedApp.'.') === 0) {
//                return $detectedApp;
//            }
//        }
//
//        return $app;
//    }
}
