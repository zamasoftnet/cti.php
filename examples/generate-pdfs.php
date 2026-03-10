<?php
require_once __DIR__ . '/../vendor/autoload.php';

$SERVER_URI = 'ctip://cti.li/';
$SOURCE_URI = 'http://cti.li/';
$OUTPUT_DIR = __DIR__ . '/../../test-output';

if (!is_dir($OUTPUT_DIR)) {
    mkdir($OUTPUT_DIR, 0777, true);
}

function with_session($filename, $setup) {
    global $SERVER_URI, $OUTPUT_DIR;
    try {
        $session = cti_get_session($SERVER_URI, array('user' => 'user', 'password' => 'kappa'));
    } catch (Exception $e) {
        fwrite(STDERR, "接続エラー: " . $e->getMessage() . "\n");
        exit(0);
    }
    $session->set_output_as_file($OUTPUT_DIR . '/' . $filename);
    try {
        $setup($session);
    } catch (Exception $e) {
        fwrite(STDERR, "エラー ($filename): " . $e->getMessage() . "\n");
        $session->close();
        exit(0);
    }
    $session->close();
    fwrite(STDERR, "生成: $filename\n");
}

// TC-01: 基本URL変換
with_session('ctip-php-url.pdf', function($session) {
    global $SOURCE_URI;
    $session->transcode($SOURCE_URI);
});

// TC-02: ハイパーリンク有効
with_session('ctip-php-hyperlinks.pdf', function($session) {
    global $SOURCE_URI;
    $session->property('output.pdf.hyperlinks', 'true');
    $session->transcode($SOURCE_URI);
});

// TC-03: ブックマーク有効
with_session('ctip-php-bookmarks.pdf', function($session) {
    global $SOURCE_URI;
    $session->property('output.pdf.bookmarks', 'true');
    $session->transcode($SOURCE_URI);
});

// TC-04: ハイパーリンクとブックマーク有効
with_session('ctip-php-hyperlinks-bookmarks.pdf', function($session) {
    global $SOURCE_URI;
    $session->property('output.pdf.hyperlinks', 'true');
    $session->property('output.pdf.bookmarks', 'true');
    $session->transcode($SOURCE_URI);
});

// TC-05: クライアント側HTML変換
with_session('ctip-php-client-html.pdf', function($session) {
    $session->start_main('dummy:///test.html');
    echo '<html><body><h1>Hello</h1><p>Client-side HTML transcoding test.</p></body></html>';
    $session->end_main();
});

// TC-06: 日本語HTMLコンテンツ
with_session('ctip-php-client-japanese.pdf', function($session) {
    $session->start_main('dummy:///japanese.html');
    echo '<html><head><meta charset="UTF-8"/></head><body>'
       . '<h1>日本語テスト</h1><p>こんにちは世界。クライアント側から日本語コンテンツを送信します。</p>'
       . '</body></html>';
    $session->end_main();
});

// TC-07: 最小HTML（境界条件）
with_session('ctip-php-client-minimal.pdf', function($session) {
    $session->start_main('dummy:///minimal.html');
    echo '<html><body><p>.</p></body></html>';
    $session->end_main();
});

// TC-08: 連続モード（2文書を結合）
with_session('ctip-php-continuous.pdf', function($session) {
    $session->set_continuous(true);
    $session->start_main('dummy:///page1.html');
    echo '<html><body><h1>Page 1</h1><p>First document in continuous mode.</p></body></html>';
    $session->end_main();
    $session->start_main('dummy:///page2.html');
    echo '<html><body><h1>Page 2</h1><p>Second document in continuous mode.</p></body></html>';
    $session->end_main();
    $session->join();
});

// TC-09: 大規模テーブル（メモリ→ファイル切り替えを誘発）
with_session('ctip-php-large-table.pdf', function($session) {
    $session->start_main('dummy:///large-table.html');
    echo '<html><head><meta charset="UTF-8"/></head><body>';
    echo '<h1>大規模テーブルテスト</h1>';
    echo '<table border="1"><tr><th>番号</th><th>名前</th><th>説明</th><th>備考</th></tr>';
    for ($i = 1; $i <= 15000; $i++) {
        echo "<tr><td>$i</td><td>項目$i</td><td>これはテスト項目 $i の詳細説明テキストです。</td><td>備考テキスト $i</td></tr>";
    }
    echo '</table></body></html>';
    $session->end_main();
});

// TC-10: 長文テキスト文書
with_session('ctip-php-large-text.pdf', function($session) {
    $sentences = 'Copper PDFはHTMLやXMLをPDFに変換するサーバーサイドのソフトウェアです。'
               . 'CTIプロトコルを通じてクライアントからドキュメントを送信し、変換結果をPDFとして受け取ります。'
               . 'このテストは大量のテキストコンテンツを含む文書を生成します。'
               . 'ドライバはPDF出力が2MBを超えた際にメモリからファイル書き出しへ切り替わります。'
               . 'このテストはその動作を確認するために設計されています。';
    $session->start_main('dummy:///large-text.html');
    echo '<html><head><meta charset="UTF-8"/></head><body>';
    for ($s = 1; $s <= 500; $s++) {
        echo "<h2>セクション $s</h2>";
        for ($p = 1; $p <= 20; $p++) {
            echo "<p>{$sentences}（セクション{$s}、段落{$p}）</p>";
        }
    }
    echo '</body></html>';
    $session->end_main();
});
