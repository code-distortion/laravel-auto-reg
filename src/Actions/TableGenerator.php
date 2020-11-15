<?php

namespace CodeDistortion\LaravelAutoReg\Actions;

use CodeDistortion\LaravelAutoReg\Core\Detect;
use CodeDistortion\LaravelAutoReg\Support\Table;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

/**
 * Takes the list of resources and builds Table object/s from them ready to show in the console.
 */
class TableGenerator
{
    /** @var Detect The detection service. */
    private Detect $detect;

    /** @var string[] The sources to limit the results to. */
    private array $sourceName = [];

    /** @var string[] The apps to limit the results to. */
    private array $apps = [];

    /** @var string[] The file-types to limit the results to. */
    private array $fileTypes = [];

    /** @var string The field to group by - splitting them in to separate tables. */
    private string $groupBy = 'default';

    /** @var boolean Show the "example" column when there are examples to show. */
    private bool $showExampleColumn = true;



    /**
     * @param Detect $detect The detection service.
     */
    public function __construct(Detect $detect)
    {
        $this->detect = $detect;
    }



    /**
     * Limit the results to the given source/s.
     *
     * @param string|string[] $sourceName The source/s to use.
     * @return $this
     */
    public function limitToSource($sourceName): self
    {
        $this->sourceName = Arr::wrap($sourceName);
        return $this;
    }

    /**
     * Limit the results to the given app/s.
     *
     * @param string|string[] $app The app/s to use.
     * @return $this
     */
    public function limitToApp($app): self
    {
        $this->apps = Arr::wrap($app);
        return $this;
    }

    /**
     * Limit the results to the given file-type/s.
     *
     * @param string|string[] $fileType The file-type/s to use.
     * @return $this
     */
    public function limitToFileType($fileType): self
    {
        $this->fileTypes = Arr::wrap($fileType);
        return $this;
    }

    /**
     * Specify what to group by - splitting them in to separate tables.
     *
     * @param string $field The field to group by.
     * @return $this
     */
    public function groupBy(string $field): self
    {
        $this->groupBy = $field;
        return $this;
    }

    /**
     * Show the "example" column when there are examples to show.
     *
     * @param boolean $show Show or hide the example column.
     * @return $this
     */
    public function showExampleWhenAvailable(bool $show): self
    {
        $this->showExampleColumn = $show;
        return $this;
    }


    /**
     * Take the Auto-Reg file resolution data, and format it into Table objects ready for displaying.
     *
     * @return Collection|Table[]
     */
    public function generateTables()
    {
        return collect(
            $this->detect
                ->getAllMeta()
                ->flatten(1)
                ->sortBy(fn($fileInfo) => [$fileInfo['source'], $fileInfo['app'], $fileInfo['type'], $fileInfo['path']])
                ->filter(fn($fileInfo) => !count($this->sourceName) || in_array($fileInfo['source'], $this->sourceName))
                ->filter(fn($fileInfo) => !count($this->apps) || in_array($fileInfo['app'], $this->apps))
                ->filter(fn($fileInfo) => !count($this->fileTypes) || in_array($fileInfo['type'], $this->fileTypes))
                ->map(fn($fileInfo) => array_merge($fileInfo, ['none' => 1]))
                ->groupBy($this->groupBy)
                ->map(fn($fileInfoSets, $app) => $fileInfoSets->map(fn($fileInfo) => [
                    $fileInfo['source'],
                    $fileInfo['app'],
                    $fileInfo['type'],
                    $fileInfo['path'],
                    $fileInfo['example'],
                ]))
                ->map(fn(Collection $rows, $name) => $this->buildTable($name, $rows))
        );
    }

    /**
     * Build a table object.
     *
     * @param string|integer|null   $name The name of the table.
     * @param Collection|string[][] $rows The rows to add to the table.
     * @return Table
     */
    private function buildTable($name, Collection $rows): Table
    {
        $headers = ['Source', 'App', 'Type', 'File / directory', 'Usage example'];
        $titleType = [
            'source' => 'Source',
            'app' => 'App',
            'type' => 'File type',
            'none' => '',
        ][$this->groupBy];

        $this->removeColumns(
            $headers,
            $rows,
            $this->detectColumnsToRemove($rows)
        );

        return new Table(
            !is_int($name) ? "$titleType: $name" : null,
            $headers,
            $rows->all()
        );
    }

    /**
     * Work out which rows should be removed.
     *
     * @param Collection|mixed[] $rows The rows that will be added to the table.
     * @return array<int, string>
     */
    private function detectColumnsToRemove(Collection $rows): array
    {
        $removeColumns = [];

        $hasExamples = $rows->pluck(3)->filter()->isNotEmpty();
        if ((!$this->showExampleColumn) || (!$hasExamples)) {
            $removeColumns[] = 'examples';
        }
        $hasGroups = $rows->pluck(0)->filter()->isNotEmpty();
        if (!$hasGroups) {
            $removeColumns[] = 'source';
        }
        if (in_array($this->groupBy, ['source', 'app', 'type'])) {
            $removeColumns[] = $this->groupBy;
        }

        return $removeColumns;
    }

    /**
     * Remove the desired columns from the headers and rows.
     *
     * @param array<int, string> $headers The headers to alter.
     * @param Collection|mixed[] $rows    The rows to alter.
     * @param array<int, string> $columns The columns to remove.
     * @return void
     */
    private function removeColumns(array &$headers, Collection &$rows, array $columns): void
    {
        $columnPositions = [
            'source' => 0,
            'app' => 1,
            'type' => 2,
            'examples' => 4,
        ];
        arsort($columnPositions);
        foreach ($columnPositions as $name => $columnPos) {
            if (in_array($name, $columns)) {
                $this->removeColumn($headers, $rows, $columnPos);
            }
        }
    }

    /**
     * Remove a particular column from the headers and rows.
     *
     * @param array<int, string> $headers The headers to alter.
     * @param Collection|mixed[] $rows    The rows to alter.
     * @param integer            $offset  The offset of the column to remove.
     * @return void
     */
    private function removeColumn(array &$headers, Collection &$rows, int $offset): void
    {
        array_splice($headers, $offset, 1);
        $rows = $rows->map(function ($values) use ($offset) {
            array_splice($values, $offset, 1);
            return $values;
        });
    }
}
