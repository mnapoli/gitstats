<?php
declare(strict_types = 1);

namespace GitStats\Formatter;

/**
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
class CsvFormatter implements Formatter
{
    public function format(array $configuration, $source) : \Generator
    {
        $taskNames = array_keys($configuration['tasks']);
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
