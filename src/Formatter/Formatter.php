<?php
declare(strict_types = 1);

namespace GitIterator\Formatter;

/**
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
interface Formatter
{
    public function format(array $configuration, $source) : \Generator;
}
