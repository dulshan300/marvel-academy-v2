@echo off
rem Local Windows helper: run psql inside the Postgres container (used by backups:restore).
rem Arguments arrive as: -U "user" -d "database" ...; SQL is read from stdin.
docker exec -i marvel-academy-pgsql-1 psql -U %~2 -d %~4 -v ON_ERROR_STOP=1 -q
