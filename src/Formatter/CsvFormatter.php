<?php
declare(strict_types = 1);

namespace GitIterator\Formatter;

/**
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
class CsvFormatter implements Formatter
{
    public function format(array $tasks, $source) : \Generator
    {
        $taskNames = array_keys($tasks);
        array_unshift($taskNames, 'commit', 'date');
        yield implode(',', $taskNames);

        foreach ($source as $data) {
            $line = array_map(function (string $str) {
                return '"' . addslashes($str) . '"';
            }, $data);
            yield implode(',', $line);
        }
    }
}
