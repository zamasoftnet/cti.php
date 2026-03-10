<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use PHPUnit\Framework\TestCase;

class IntegrationTest extends TestCase
{
    private static $config = null;
    private static $serverAvailable = false;

    private $session = null;

    private static function dataPath(string $name): string
    {
        return __DIR__ . '/data/' . $name;
    }

    public static function setUpBeforeClass(): void
    {
        $configPath = __DIR__ . '/../test-config.json';
        if (file_exists($configPath)) {
            $json = file_get_contents($configPath);
            self::$config = json_decode($json, true);
        }

        if (self::$config !== null) {
            $host = self::$config['host'] ?? 'localhost';
            $port = self::$config['port'] ?? 8099;
            $fp = @fsockopen($host, $port, $errno, $errstr, 2);
            if ($fp !== false) {
                fclose($fp);
                self::$serverAvailable = true;
            }
        }
    }

    protected function setUp(): void
    {
        if (self::$config === null) {
            $this->markTestSkipped('test-config.json が見つかりません。');
        }
        if (!self::$serverAvailable) {
            $host = self::$config['host'] ?? 'localhost';
            $port = self::$config['port'] ?? 8099;
            $this->markTestSkipped("Copper PDF サーバー ({$host}:{$port}) に接続できません。");
        }

        @mkdir(__DIR__ . '/out', 0777, true);
    }

    protected function tearDown(): void
    {
        if ($this->session !== null) {
            try {
                $this->session->close();
            } catch (\Exception $e) {
                // ignore
            }
            $this->session = null;
        }
    }

    private function createSession(): \CTI\Session
    {
        $host = self::$config['host'] ?? 'localhost';
        $port = self::$config['port'] ?? 8099;
        $user = self::$config['user'] ?? 'user';
        $password = self::$config['password'] ?? 'kappa';

        $uri = "ctip://{$host}:{$port}/";
        $this->session = cti_get_session($uri, [
            'user' => $user,
            'password' => $password
        ]);
        return $this->session;
    }

    private function transcodeHtml(\CTI\Session $session, string $outputFile): void
    {
        $session->set_output_as_file($outputFile);

        $session->start_resource('test.css');
        echo file_get_contents(self::dataPath('test.css'));
        $session->end_resource();

        $session->start_main('test.html', ['mimeType' => 'text/html']);
        echo file_get_contents(self::dataPath('test.html'));
        $session->end_main();
    }

    private static function assertPdf(string $path): void
    {
        self::assertFileExists($path);
        $fp = fopen($path, 'rb');
        $header = fread($fp, 4);
        fclose($fp);
        self::assertEquals('%PDF', $header, 'PDFヘッダーが正しいこと');
    }

    public function testConnection(): void
    {
        $session = $this->createSession();
        $this->assertNotNull($session);
        $session->close();
        $this->session = null;
    }

    public function testServerInfo(): void
    {
        $session = $this->createSession();
        $info = $session->get_server_info('http://www.cssj.jp/ns/ctip/version');
        $this->assertNotEmpty($info);
    }

    public function testHtmlToPdfFile(): void
    {
        $outFile = __DIR__ . '/out/php-output-file.pdf';
        if (file_exists($outFile)) {
            unlink($outFile);
        }

        $session = $this->createSession();
        $this->transcodeHtml($session, $outFile);

        self::assertPdf($outFile);
    }

    public function testOutputToDirectory(): void
    {
        $outputDir = __DIR__ . '/out/output-dir';
        if (is_dir($outputDir)) {
            foreach (scandir($outputDir) as $f) {
                if ($f !== '.' && $f !== '..') {
                    unlink($outputDir . '/' . $f);
                }
            }
        } else {
            mkdir($outputDir, 0777, true);
        }

        $session = $this->createSession();
        $session->property('output.type', 'image/jpeg');
        $session->set_output_as_directory($outputDir, '', '.jpg');

        $session->start_main('test.html', ['mimeType' => 'text/html']);
        echo file_get_contents(self::dataPath('test.html'));
        $session->end_main();

        $jpgs = glob($outputDir . '/*.jpg');
        $this->assertGreaterThan(0, count($jpgs), '出力ディレクトリにJPEGファイルが生成される');
    }

    public function testPropertySetting(): void
    {
        $session = $this->createSession();
        $var = '';
        $session->set_output_as_variable($var);
        $session->property('output.pdf.version', '1.5');

        $session->start_main('test.html', ['mimeType' => 'text/html']);
        echo file_get_contents(self::dataPath('test.html'));
        $session->end_main();

        $this->assertStringStartsWith('%PDF-', $var);
    }

    public function testResolverCallback(): void
    {
        $outFile = __DIR__ . '/out/php-resolver.pdf';
        if (file_exists($outFile)) {
            unlink($outFile);
        }

        $resolved = false;
        $dataDir = self::dataPath('');

        $session = $this->createSession();
        $session->set_resolver_func(function (string $uri, $resource) use (&$resolved, $dataDir) {
            if ($uri === 'test.css') {
                $resolved = true;
                $resource->start();
                echo file_get_contents($dataDir . 'test.css');
            }
        });
        $session->set_output_as_file($outFile);

        $session->start_main('test.html', ['mimeType' => 'text/html']);
        echo file_get_contents(self::dataPath('test.html'));
        $session->end_main();

        $this->assertTrue($resolved, 'resolver が呼ばれてリソースを解決できる');
        self::assertPdf($outFile);
    }

    public function testProgressCallback(): void
    {
        $progress = [];
        $session = $this->createSession();
        $session->set_results(new \CTI\Results\SingleResult(new \CTI\Builder\NullBuilder()));
        $session->set_progress_func(function ($length, $read) use (&$progress) {
            $progress[] = [$length, $read];
        });
        $session->property('input.include', 'https://www.w3.org/**');
        $session->transcode('https://www.w3.org/TR/xslt-10/');

        $this->assertGreaterThan(0, count($progress), '進行状況コールバックが呼ばれる');
    }

    public function testReset(): void
    {
        $out1 = __DIR__ . '/out/php-reset-1.pdf';
        $out2 = __DIR__ . '/out/php-reset-2.pdf';
        foreach ([$out1, $out2] as $f) {
            if (file_exists($f)) {
                unlink($f);
            }
        }

        $session = $this->createSession();
        $this->transcodeHtml($session, $out1);
        $session->reset();
        $this->transcodeHtml($session, $out2);

        self::assertPdf($out1);
        self::assertPdf($out2);
    }

    public function testAuthenticationFailure(): void
    {
        $host = self::$config['host'] ?? 'localhost';
        $port = self::$config['port'] ?? 8099;
        $uri = "ctip://{$host}:{$port}/";

        $this->expectException(\Exception::class);
        $session = cti_get_session($uri, [
            'user' => 'invalid-user',
            'password' => 'invalid-password'
        ]);
    }
}
