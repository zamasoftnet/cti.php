<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use PHPUnit\Framework\TestCase;

class IntegrationTest extends TestCase
{
    private static $config = null;
    private static $serverAvailable = false;
    /** 解決済みの接続先 URI(スキーム込み)。環境変数 > test-config.json */
    private static $serverUri = null;
    /** 接続試験マトリクスの共通契約: CTI_EXPECT_REJECT=1 で拒否試験だけを走らせる */
    private static $expectReject = false;

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

        // 接続試験マトリクスの共通契約(copperpdf4/docs/design/2026-09-20-cti-driver-tls-test-matrix-design.md §2):
        // CTI_SERVER_URI(スキーム込み) > test-config.json の host/port。PHP 版には証明書の検証を省く指定が
        // 無いので CTI_TLS_INSECURE=1 は設定エラー
        $envUri = getenv('CTI_SERVER_URI');
        if ($envUri !== false && $envUri !== '') {
            self::$config = [
                'user' => getenv('CTI_TEST_USER') ?: (self::$config['user'] ?? 'user'),
                'password' => getenv('CTI_TEST_PASSWORD') ?: (self::$config['password'] ?? 'kappa'),
            ];
            self::$serverUri = $envUri;
        } elseif (self::$config !== null) {
            self::$serverUri = 'ctip://' . (self::$config['host'] ?? 'localhost') . ':' . (self::$config['port'] ?? 8099) . '/';
        }
        if (getenv('CTI_TLS_INSECURE') === '1') {
            throw new \RuntimeException('CTI_TLS_INSECURE は PHP 版では使えません(証明書を検証しない指定がありません)');
        }
        self::$expectReject = getenv('CTI_EXPECT_REJECT') === '1';

        if (self::$serverUri !== null) {
            if (preg_match('#^ctips?://([^:/]+)(?::([0-9]+))?/?$#', self::$serverUri, $m)) {
                $host = $m[1];
                $port = isset($m[2]) && $m[2] !== '' ? (int)$m[2] : 8099;
                $fp = @fsockopen($host, $port, $errno, $errstr, 2);
                if ($fp !== false) {
                    fclose($fp);
                    self::$serverAvailable = true;
                }
            }
        }
    }

    protected function setUp(): void
    {
        if (self::$config === null) {
            $this->markTestSkipped('test-config.json が見つかりません。');
        }
        if (!self::$serverAvailable) {
            // 到達不能は失敗(黙って skip にしない。2026-09-20、マトリクスの契約)
            $this->fail('Copper PDF サーバー (' . self::$serverUri . ') に接続できません。');
        }
        if (self::$expectReject && $this->name() !== 'testCertificateRejection') {
            $this->markTestSkipped('CTI_EXPECT_REJECT=1: 拒否試験だけを走らせます。');
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

    private function createSession(?string $user = null, ?string $password = null): \CTI\Session
    {
        $this->session = cti_get_session(self::$serverUri, [
            'user' => $user ?? (self::$config['user'] ?? 'user'),
            'password' => $password ?? (self::$config['password'] ?? 'kappa')
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
        $this->expectException(\Exception::class);
        $this->createSession('invalid-user', 'invalid-password');
    }

    /**
     * 拒否試験(tls-reject / tls-badname)。CTI_EXPECT_REJECT=1 のときだけ走り、接続が証明書の検証で
     * 拒否されることを確かめる。変換まで進む・接続拒否・認証失敗は成功に数えない。
     * PHP の fsockopen('tls://…') は失敗を警告+false で返し、ドライバは例外にする。
     */
    public function testCertificateRejection(): void
    {
        if (!self::$expectReject) {
            $this->markTestSkipped('CTI_EXPECT_REJECT=1 のときだけ走ります。');
        }
        $message = null;
        set_error_handler(function ($errno, $errstr) use (&$message) {
            $message .= $errstr . ' ';
            return true;
        });
        try {
            $this->createSession();
            $this->fail('証明書の検証で拒否されなかった(接続できてしまった)');
        } catch (\PHPUnit\Framework\AssertionFailedError $e) {
            throw $e;
        } catch (\Exception $e) {
            $message .= $e->getMessage();
        } finally {
            restore_error_handler();
        }
        fwrite(STDOUT, 'CTI-MATRIX reject: ' . trim($message) . PHP_EOL);
        $this->assertMatchesRegularExpression('/certificate verify failed|did not match/i', $message);
    }
}
