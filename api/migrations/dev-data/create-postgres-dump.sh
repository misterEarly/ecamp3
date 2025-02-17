#!/bin/sh

docker-compose exec database pg_dump \
                                -U ecamp3 \
                                -d ecamp3dev \
                                -a \
                                --column-inserts \
                                --exclude-table=doctrine_migration_versions \
                                --exclude-table=content_type \
                                --rows-per-insert 10000 \
  | grep -v -e "pg_dump" \
  | grep -v "^--" \
  | grep -v "pg_dump" \
  | grep -v "SET" \
  | grep -v "SELECT pg_catalog" \
  | dos2unix \
  > api/migrations/dev-data/Version$(date +"%Y%m%d%I%M%p")_data.sql
