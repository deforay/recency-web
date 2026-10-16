# Database migrations

Each `.sql` file here changes the database schema once. `composer migrate` applies the files the database has not had yet, in file-name order, and records each in the `schema_migrations` table. `composer update` runs it too.

## Add a migration

1. Create a file named `YYYYMMDD-NN-what-it-does.sql`, for example `20261020-01-add-login-id-to-users.sql`. `NN` orders files with the same date.
2. End each statement with `;` at the end of a line. `DELIMITER` blocks are not supported.
3. Write each statement so that running the file a second time is safe, for example `ADD COLUMN IF NOT EXISTS`. MySQL commits each schema change on its own, so a file that fails halfway leaves its earlier statements applied.
4. Run `composer migrate` and check the result.
5. Commit the file. Never edit a file after it has been applied anywhere. Add a new file instead.

## Check what is pending

```
vendor/bin/laminas migrate --status
```

## History

`data/alter.sql` holds the schema changes made before migrations existed. `sql/schema.sql` is the schema with all of them applied, for a new install.
