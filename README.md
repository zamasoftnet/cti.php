# CTI Driver for PHP

[![License](https://img.shields.io/badge/License-Apache%202.0-blue.svg)](https://opensource.org/licenses/Apache-2.0)

[Copper PDF](https://copper-pdf.com/) 文書変換サーバーに接続するためのPHPドライバです。

## インストール

### Composer（推奨）

```bash
composer require mimidesunya/cti.php
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

## ドキュメント

- [APIドキュメント](https://mimidesunya.github.io/cti.php/)
- [オンラインマニュアル](http://dl.cssj.jp/docs/copper/3.0/html/3422_ctip2_php.html)

## ライセンス

Copyright (c) 2011-2024 Zamasoft.

Apache License Version 2.0 に基づいてライセンスされます。

詳細は [LICENSE](LICENSE) ファイルを参照してください。

## 変更履歴

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
