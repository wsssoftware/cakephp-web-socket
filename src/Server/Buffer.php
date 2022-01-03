<?php
declare(strict_types=1);

namespace WebSocket\Server;

use RuntimeException;

/**
 * Class Buffer
 * Created by allancarvalho in dezembro 17, 2021
 */
class Buffer
{
    /**
     * Write to stream.
     *
     * @param resource $resource WebSocket resource
     * @param string $string string to write
     * @return int
     */
    public static function write($resource, string $string): int
    {
        $stringLength = strlen($string);
        if ($stringLength === 0) {
            return 0;
        }

        for ($written = 0; $written < $stringLength; $written += $fwrite) {
            // @codingStandardsIgnoreStart
            $fwrite = @fwrite($resource, substr($string, $written));
            // @codingStandardsIgnoreEnd
            if ($fwrite === false) {
                throw new RuntimeException('Could not write to stream.');
            }
            if ($fwrite === 0) {
                throw new RuntimeException('Could not write to stream.');
            }
        }

        return $written;
    }

    /**
     * Reads from stream.
     *
     * @param resource $resource WebSocket resource
     * @throws \RuntimeException
     * @return string
     */
    public static function read($resource): string
    {
        $buffer = '';
        $buffSize = 8192;
        $metadata['unread_bytes'] = 0;
        do {
            if (feof($resource)) {
                throw new RuntimeException('Could not read from stream.');
            }
            $result = fread($resource, $buffSize);
            if ($result === false || feof($resource)) {
                throw new RuntimeException('Could not read from stream.');
            }
            $buffer .= $result;
            $metadata = stream_get_meta_data($resource);
            $buffSize = $metadata['unread_bytes'] > $buffSize ? $buffSize : $metadata['unread_bytes'];
        } while ($metadata['unread_bytes'] > 0);

        return $buffer;
    }
}
