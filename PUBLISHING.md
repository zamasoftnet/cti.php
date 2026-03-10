# リリース手順

## リリース方法

バージョンタグを push します（`composer.json` にバージョンフィールドは不要）。

```bash
git tag v2.1.5
git push origin v2.1.5
```

GitHub Actions が以下を自動実行します：

1. phpDocumentor によるAPIドキュメント生成
2. GitHub Releases にアーカイブを公開（`cti-php-{VERSION}.zip` / `.tar.gz`）
3. GitHub Pages にドキュメントをデプロイ

## Packagist への公開

Git タグを push すると Packagist に自動反映されます（要: Webhook 設定）。

### 初回登録

1. https://packagist.org/ にアクセスしてログイン
2. 「Submit」でリポジトリ URL `https://github.com/zamasoftnet/cti.php` を登録
3. GitHub Webhook を設定（自動更新のため）

### インストール確認

```bash
composer require zamasoft/cti-php
```

## ドキュメント

- **GitHub Pages**: https://zamasoftnet.github.io/cti.php/
- リリース時に自動更新
