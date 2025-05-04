#!/bin/bash
# This script does 2 things - makes a new backup file and deletes any older than a week
# This expects that a remote client copies these created files elsewhere

set -euo pipefail

DATABASES="curios glossary primes"
TIMESTAMP=$(date +%F)
DELETE_OLDER_THAN=7

mkdir -p /backup
cd /backup

for database in $DATABASES
do
	mysqldump $database | gzip > "${database}_${TIMESTAMP}.sql.gz"
done

find . -type f -name "*.sql.gz" -mtime +$DELETE_OLDER_THAN -exec rm {} \;

