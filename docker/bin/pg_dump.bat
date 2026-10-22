@echo off
rem Local Windows helper: run pg_dump inside the Postgres container.
rem spatie/db-dumper passes -U "user" as the first arguments and the database in PGDATABASE;
rem connecting over the container socket needs no password. Output goes to stdout.
docker exec -i marvel-academy-pgsql-1 pg_dump -U %~2 --no-owner %PGDATABASE%
