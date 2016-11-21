<?php
declare(strict_types = 1);

namespace GitIterator\Formatter;

/**
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
interface Formatter
{
    public function format(array $tasks, $source) : \Generator;
}
