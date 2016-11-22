<?php
declare(strict_types = 1);

namespace GitIterator\Formatter;

/**
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
class SqlFormatter implements Formatter
{
    public function format(array $configuration, $source) : \Generator
    {
        $table = $configuration['sql']['table'] ?? null;
        if (!$table) {
            throw new \Exception('You must set a `sql.table` key in the configuration');
        }

        $tasks = $configuration['tasks'];
        $taskNames = array_keys($tasks);
        array_unshift($taskNames, 'commit', 'date');
        $columns = array_map(function (string $taskName) {
            return '`' . $taskName . '`';
        }, $taskNames);
        $columnsForUpdate = array_map(function (string $column) {
            // a=VALUES(a) avoids repeating the values of each column
            // see http://stackoverflow.com/a/9537799/245552
            return sprintf('%s=VALUES(%s)', $column, $column);
        }, $columns);

        foreach ($source as $data) {
            $data = array_map(function (string $str) {
                return '"' . $this->escape($str) . '"';
            }, $data);

            // Insert or update to avoid loosing values in other extra
            // columns in the same table
            yield sprintf(
                'INSERT INTO `%s` (%s) VALUES (%s) ON DUPLICATE KEY UPDATE %s;',
                $table,
                implode(', ', $columns),
                implode(', ', $data),
                implode(', ', $columnsForUpdate)
            );
        }
    }

    private function escape(string $str)
    {
        $search = array("\\",  "\x00", "\n",  "\r",  "'",  '"', "\x1a");
        $replace = array("\\\\","\\0","\\n", "\\r", "\'", '\"', "\\Z");

        return str_replace($search, $replace, $str);
    }
}
