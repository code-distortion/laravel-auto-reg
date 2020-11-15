<?php

namespace CodeDistortion\LaravelAutoReg\Support;

/**
 * Represent a table of values.
 */
class Table
{
    /** @var string|null The table's name. */
    private ?string $name;

    /** @var string[] The table's headers. */
    private array $headers;

    /** @var string[][] The table's rows. */
    private array $rows;



    /**
     * @param string|null $name    The table's name.
     * @param string[]    $headers The table's headers.
     * @param string[][]  $rows    The table's rows.
     */
    public function __construct(?string $name, array $headers, array $rows)
    {
        $this->name = $name;
        $this->headers = $headers;
        $this->rows = $rows;
    }

    /**
     * Get the table's name.
     *
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Get the table headers.
     *
     * @return string[]
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Get the table rows.
     *
     * @return string[][]
     */
    public function getRows(): array
    {
        return $this->rows;
    }
}
