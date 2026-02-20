<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use PHPUnit\Framework\TestCase;

class IntegrationTest extends TestCase
{
    private static $config = null;
    private static $configLoaded = false;
    private $session = null;

    public static function setUpBeforeClass(): void
    {
        $configPath = __DIR__ . '/../test-config.json';
        if (file_exists($configPath)) {
            $json = file_get_contents($configPath);
            self::$config = json_decode($json, true);
        }
        self::$configLoaded = true;
    }

    protected function setUp(): void
    {
        if (self::$config === null) {
            $this->markTestSkipped('test-config.json が見つかりません。テストをスキップします。');
        }

        $host = self::$config['host'] ?? 'localhost';
        $port = self::$config['port'] ?? 8099;

        // サーバー接続確認
        $fp = @fsockopen($host, $port, $errno, $errstr, 3);
        if ($fp === false) {
            $this->markTestSkipped("Copper PDF サーバー ($host:$port) に接続できません。テストをスキップします。");
        }
        fclose($fp);
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

    private function createSession()
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

    public function testPropertySetting(): void
    {
        $session = $this->createSession();
        $var = '';
        $session->set_output_as_variable($var);
        $session->property('output.pdf.version', '1.5');

        $session->start_main('.');
        echo '<html><body><p>Property Test</p></body></html>';
        $session->end_main();

        $this->assertStringStartsWith('%PDF-', $var);
    }

    public function testHtmlToPdfConversion(): void
    {
        $session = $this->createSession();
        $var = '';
        $session->set_output_as_variable($var);

        $session->start_main('.');
        echo '<html><body><h1>Hello Copper PDF</h1><p>Integration Test</p></body></html>';
        $session->end_main();

        // PDFヘッダーの確認
        $this->assertStringStartsWith('%PDF-', $var);
        // PDFデータが生成されていることを確認
        $this->assertGreaterThan(100, strlen($var));
    }

    public function testOutputToFile(): void
    {
        $session = $this->createSession();

        $outDir = __DIR__ . '/out';
        @mkdir($outDir, 0777, true);
        $outFile = $outDir . '/test-output.pdf';

        $session->set_output_as_file($outFile);

        $session->start_main('.');
        echo '<html><body><p>File Output Test</p></body></html>';
        $session->end_main();

        $session->close();
        $this->session = null;

        $this->assertFileExists($outFile);
        $content = file_get_contents($outFile);
        $this->assertStringStartsWith('%PDF-', $content);

        // クリーンアップ
        @unlink($outFile);
    }

    public function testReset(): void
    {
        $session = $this->createSession();
        $var = '';
        $session->set_output_as_variable($var);

        $session->start_main('.');
        echo '<html><body><p>Before Reset</p></body></html>';
        $session->end_main();

        $this->assertStringStartsWith('%PDF-', $var);

        // リセット後に再度変換
        $session->reset();

        $var2 = '';
        $session->set_output_as_variable($var2);

        $session->start_main('.');
        echo '<html><body><p>After Reset</p></body></html>';
        $session->end_main();

        $this->assertStringStartsWith('%PDF-', $var2);
    }
}
