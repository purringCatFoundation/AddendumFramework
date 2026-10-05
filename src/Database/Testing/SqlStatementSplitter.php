<?php

declare(strict_types=1);

namespace PCF\Addendum\Database\Testing;

use Ds\Vector;

final class SqlStatementSplitter
{
    /** @return Vector<string> */
    public function split(string $sql): Vector
    {
        $statements = new Vector();
        $current = '';
        $length = strlen($sql);

        for ($index = 0; $index < $length; ++$index) {
            $character = $sql[$index];
            if ($character === "'" || $character === '"') {
                $current .= $this->quotedSegment($sql, $index);
                continue;
            }
            if ($character === ';') {
                $statements->push($current);
                $current = '';
                continue;
            }
            $current .= $character;
        }

        if (trim($current) !== '') {
            $statements->push($current);
        }

        return $statements;
    }

    private function quotedSegment(string $sql, int &$index): string
    {
        $quote = $sql[$index];
        $segment = $quote;
        $length = strlen($sql);

        while (++$index < $length) {
            $segment .= $sql[$index];
            if ($sql[$index] !== $quote) {
                continue;
            }
            if (($sql[$index + 1] ?? null) === $quote) {
                $segment .= $sql[++$index];
                continue;
            }
            break;
        }

        return $segment;
    }
}
