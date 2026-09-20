# CTI Driver for PHP

[![License](https://img.shields.io/badge/License-Apache%202.0-blue.svg)](https://opensource.org/licenses/Apache-2.0)

[Copper PDF](https://copper-pdf.com/) 文書変換サーバーに接続するためのPHPドライバです。

## API ドキュメント

- **オンライン**: https://zamasoftnet.github.io/cti.php/

## インストール

### Composer（GitHub VCS / Packagist）

GitHub リポジトリから直接インストールする場合は、先に VCS リポジトリを登録してください。

```bash
composer config repositories.zamasoftnet-cti-php vcs https://github.com/zamasoftnet/cti.php
composer require zamasoft/cti-php
```

Packagist に登録済みの環境では、`composer require zamasoft/cti-php` だけでもインストールできます。

`composer.json` の `repositories` に直接追加する場合の例:

```json
{
  "repositories": [
    {
      "type": "vcs",
      "url": "https://github.com/zamasoftnet/cti.php"
    }
  ],
  "require": {
    "zamasoft/cti-php": "*"
  }
}
```

### 手動インストール

`src/CTI/` ディレクトリをプロジェクトにコピーし、`require_once` で読み込んでください。

```php
require_once 'path/to/CTI/DriverManager.php';
```

## 動作環境

- PHP 5.6 以降

## 基本的な使い方

```php
<?php
require_once 'vendor/autoload.php';

// セッションを取得
$session = cti_get_session('http://localhost:8099/');

// ソースを設定
$session->set_source_uri('http://example.com/document.html');

// 出力先を設定して変換
$session->start_main('./output.pdf');
?>
```

## TLS 接続

`ctips://` で接続すると、サーバー証明書を PHP(OpenSSL)の既定の認証局ストアで検証し、ホスト名も照合します。
自己署名や独自の認証局の証明書は `openssl.cafile` などで認証局を追加してください。

試験用に検証を省くには、オプションに `'insecure' => true` を渡します(2.2.1 以降。証明書もホスト名も
確かめないので、本番では使わないでください)。

```php
$session = cti_get_session('ctips://localhost:8094/', [
    'user' => 'user', 'password' => 'kappa', 'insecure' => true
]);
```

## API概要

### Session クラスの主要メソッド

| メソッド | 説明 |
| :--- | :--- |
| `set_output_as_file($file)` | PDFをファイルに出力 |
| `set_output_as_resource($fp)` | PDFをストリームリソースに出力 |
| `set_output_as_variable(&$var)` | PDFを変数に出力 |
| `set_output_as_directory($dir, $prefix, $suffix)` | PDFをディレクトリに連番で出力 |
| `set_message_func($func)` | メッセージコールバックを設定 |
| `set_progress_func($func)` | 進捗コールバックを設定 |
| `set_resolver_func($func)` | リソース解決コールバックを設定 |
| `property($name, $value)` | プロパティを設定 |
| `start_main($uri, $opts)` | メイン文書の送信を開始 |
| `end_main()` | メイン文書の送信を完了 |
| `start_resource($uri, $opts)` | リソースの送信を開始 |
| `end_resource()` | リソースの送信を完了 |
| `transcode($uri)` | サーバー側リソースを変換 |
| `set_continuous($continuous)` | 連続モードの設定 |
| `join()` | 結果の結合 |
| `reset()` | セッションのリセット |
| `close()` | セッションのクローズ |

## テストの実行方法

テストにはCopper PDFサーバーへの接続が必要な統合テストと、接続不要なユーティリティ単体テストがあります。

1. `test-config.json` を作成:
```json
{
  "host": "localhost",
  "port": 8099,
  "user": "user",
  "password": "kappa"
}
```

2. PHPUnit でテストを実行:
```bash
./vendor/bin/phpunit
```

サーバーが起動していない場合、統合テストは自動的にスキップされ、`HelpersTest` は単体で実行されます。

Ant から実行する場合:

```bash
ant test
```

## ドキュメント

- [APIドキュメント](https://zamasoftnet.github.io/cti.php/)
- [オンラインマニュアル](http://dl.cssj.jp/docs/copper/3.0/html/3422_ctip2_php.html)

### ドキュメント生成

phpDocumentorを使用してAPIドキュメントを生成できます:

```bash
./vendor/bin/phpdoc
```

または Ant を使用:

```bash
ant doc
```

Ant で配布アーカイブを作る場合:

```bash
ant dist
```

## ライセンス

Copyright (c) 2011-2025 Zamasoft.

Apache License Version 2.0 に基づいてライセンスされます。

詳細は [LICENSE](LICENSE) ファイルを参照してください。

## 変更履歴

### v2.2.1 (2026/9/20)
- 試験用に証明書の検証を省くオプション `'insecure' => true` を追加(他言語版の Java `--insecure`、
  .NET `?insecure=1`、Ruby/Perl/Python の `insecure` に相当)。接続は `fsockopen` から
  `stream_socket_client` に変更(既定の検証動作は同じ)。

### v2.2.0 (2025/12/30)
- Composer対応

### v2.1.4 (2021/11/15)
- 'Only variables should be passed by reference' 警告が出ないように対応

### v2.1.3 (2014/08/11)
- Session->start_main関数のデフォルトの引数を'.'に変更
- 画像を標準出力に出力時のヘッダ警告問題に対応

### v2.1.2 (2013/04/24)
- コールバック関数を参照渡しから値渡しに修正

### v2.1.1 (2011/03/16)
- PHP 5.1.6 以降に対応
- Content-Length ヘッダの不具合を修正

### v2.1.0 (2011/03/03)
- 複数文書から1つのPDFを生成する機能に対応
- TLS通信に対応

### v2.0.0 (2010/11/02)
- 最初のリリース
