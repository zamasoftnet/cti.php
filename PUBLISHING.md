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

Packagist の「This package is not auto-updated」表示を消して push 時に即時更新させるには、GitHub webhook の設定が必要です。
Packagist 未登録でも、利用側で VCS リポジトリを指定すれば `zamasoft/cti-php` として Composer からインストールできます。

### 初回登録

1. https://packagist.org/ にアクセスしてログイン
2. 「Submit」でリポジトリ URL `https://github.com/zamasoftnet/cti.php` を登録
3. GitHub リポジトリの Secrets に以下を登録
   - `PACKAGIST_USERNAME`
   - `PACKAGIST_API_TOKEN`
   - `GH_ADMIN_TOKEN`
4. GitHub Actions の `Ensure Packagist GitHub Hook` を一度実行
5. Packagist のパッケージページから auto-updated 警告が消えたことを確認

`GH_ADMIN_TOKEN` は GitHub webhook を作成・更新できるトークンにしてください。

- classic PAT の場合: `admin:repo_hook`
- fine-grained token の場合: 対象リポジトリの Webhooks を write

手動で設定する場合は、GitHub Webhook を次の値で作成します。

- Payload URL: `https://packagist.org/api/github?username=PACKAGIST_USERNAME`
- Content Type: `application/json`
- Secret: Packagist API Token
- Event: `push`

既存の `Notify Packagist (fallback)` workflow は、hook 設定前後を問わず Packagist の手動更新 API を叩く予備経路です。Packagist の auto-updated 表示自体は、この workflow だけでは解消しません。

### インストール確認

```bash
composer config repositories.zamasoftnet-cti-php vcs https://github.com/zamasoftnet/cti.php
composer require zamasoft/cti-php
```

## ドキュメント

- **GitHub Pages**: https://zamasoftnet.github.io/cti.php/
- リリース時に自動更新
