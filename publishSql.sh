#!/bin/bash
# This gets the latest schema and makes it publically available.
# Might eventually be extended to data, but needs to be carefully done
# in order to avoid exposing private data.

set -euo pipefail

mysqldump --no-data --databases primes curios glossary > /var/www/html/public.sql