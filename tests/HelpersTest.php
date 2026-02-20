<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use PHPUnit\Framework\TestCase;

final class HelpersTest extends TestCase
{
    public function testShortReadWrite(): void
    {
        $fp = fopen('php://temp', 'r+');
        $values = [0x0000, 0x1234, 0xFFFF];

        foreach ($values as $value) {
            cti_utils_write_short($fp, $value);
        }
        rewind($fp);

        foreach ($values as $value) {
            $this->assertSame($value, cti_utils_read_short($fp));
        }
    }

    public function testIntAndByteReadWrite(): void
    {
        $fp = fopen('php://temp', 'r+');

        cti_utils_write_int($fp, 0x89ABCDEF);
        cti_utils_write_long($fp, 0x100000001);
        cti_utils_write_byte($fp, 0x5A);
        rewind($fp);

        $this->assertSame(0x89ABCDEF, cti_utils_read_int($fp));
        $this->assertSame(0x100000001, cti_utils_read_long($fp));
        $this->assertSame(0x5A, cti_utils_read_byte($fp));
    }

    public function testBytesReadWriteWithBinaryData(): void
    {
        $fp = fopen('php://temp', 'r+');
        $payload = "integration-bytes\x00\xFF\x01";

        cti_utils_write_bytes($fp, $payload);
        rewind($fp);

        $this->assertSame($payload, cti_utils_read_bytes($fp));
    }

    public function testEmptyBytesReadWrite(): void
    {
        $fp = fopen('php://temp', 'r+');
        $payload = '';

        cti_utils_write_bytes($fp, $payload);
        rewind($fp);

        $this->assertSame($payload, cti_utils_read_bytes($fp));
    }
}
