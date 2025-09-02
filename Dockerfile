# 1. ベースとなる公式イメージを選択 (PHP 8.2 と Apache が入っているもの)
FROM php:8.2-apache

# 2. 作成したApacheの設定ファイルをコンテナ内にコピーして上書き
COPY apache-config.conf /etc/apache2/sites-available/000-default.conf

# 3. プロジェクトの全ファイルをコンテナの /var/www/html ディレクトリにコピー
COPY . /var/www/html/
