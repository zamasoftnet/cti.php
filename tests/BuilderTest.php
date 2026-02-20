<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use PHPUnit\Framework\TestCase;
use CTI\Builder\StreamBuilder;

final class BuilderTest extends TestCase
{
    public function testWritesBlocksToOutputInOrder(): void
    {
        $output = '';
        $builder = new StreamBuilder($output);

        $builder->add_block();
        $builder->add_block();

        $left = 'Hello ';
        $right = 'World!';
        $builder->write(0, $left);
        $builder->write(1, $right);

        $builder->finish();

        $this->assertSame('Hello World!', $output);
    }

    public function testInsertBlockBeforeAnchor(): void
    {
        $output = '';
        $builder = new StreamBuilder($output);

        $builder->add_block();
        $builder->add_block();

        $left = 'A';
        $right = 'C';
        $builder->write(0, $left);
        $builder->write(1, $right);
        $builder->insert_block_before(1);
        $insert = 'B';
        $builder->write(2, $insert);

        $builder->finish();

        $this->assertSame('ABC', $output);
    }

    public function testLargeDataFallsBackToTempFile(): void
    {
        $output = '';
        $builder = new StreamBuilder($output);
        $payload = str_repeat('A', 300);

        $builder->add_block();
        $blockData = $payload;
        $builder->write(0, $blockData);

        $builder->finish();

        $this->assertSame(300, strlen($output));
        $this->assertSame($payload, $output);
    }

    public function testSerialWriteWritesImmediately(): void
    {
        $output = 'prefix:';
        $builder = new StreamBuilder($output);

        $seed = 'seed-';
        $builder->serial_write($seed);
        $builder->add_block();
        $body = 'stream-data';
        $builder->write(0, $body);
        $builder->finish();

        $this->assertSame('prefix:seed-stream-data', $output);
    }
}
