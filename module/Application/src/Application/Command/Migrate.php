<?php

namespace Application\Command;

use PDO;
use Throwable;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Applies the SQL files in migrations/ that this database has not had yet.
 *
 * Each file runs once, in file-name order, and is recorded in schema_migrations.
 * Name files YYYYMMDD-NN-what-it-does.sql so the order is the date order.
 *
 * A file holds one or more statements, each ending with ; at the end of a line.
 * DELIMITER blocks are not supported. MySQL commits each DDL statement on its
 * own, so a failed file can leave the statements before the failing one applied;
 * write each statement so that running the file again is safe.
 *
 * data/alter.sql is the history before migrations existed. A database is
 * expected to have all of it already.
 */
class Migrate extends Command
{
    private const LOCK_NAME = 'recency_migrate';

    private PDO $pdo;

    /** @param callable(): PDO $connect opens the database connection when the command runs */
    public function __construct(private $connect, private string $directory)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('Apply the database migrations this install has not had yet');
        $this->addOption('status', null, InputOption::VALUE_NONE, 'List the pending migrations and apply nothing');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->pdo = ($this->connect)();
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Two runs at once, such as two composer installs, would apply a file twice
        if ((int) $this->pdo->query("SELECT GET_LOCK('" . self::LOCK_NAME . "', 0)")->fetchColumn() !== 1) {
            $output->writeln('<error>Another migration run holds the lock. Try again when it finishes.</error>');
            return Command::FAILURE;
        }

        try {
            $this->pdo->exec(
                "CREATE TABLE IF NOT EXISTS schema_migrations (
                    migration VARCHAR(255) NOT NULL PRIMARY KEY,
                    applied_at DATETIME NOT NULL
                )"
            );

            $pending = $this->pending();
            if ($pending === []) {
                $output->writeln('No pending migrations.');
                return Command::SUCCESS;
            }

            if ($input->getOption('status')) {
                foreach ($pending as $file) {
                    $output->writeln('Pending: ' . basename($file));
                }
                return Command::SUCCESS;
            }

            foreach ($pending as $file) {
                $name = basename($file);
                try {
                    foreach (self::statements((string) file_get_contents($file)) as $statement) {
                        $this->pdo->exec($statement);
                    }
                } catch (Throwable $e) {
                    $output->writeln("<error>$name failed: {$e->getMessage()}</error>");
                    $output->writeln('Fix the file or the database, then run the migrations again.');
                    return Command::FAILURE;
                }
                $record = $this->pdo->prepare('INSERT INTO schema_migrations (migration, applied_at) VALUES (?, NOW())');
                $record->execute([$name]);
                $output->writeln("Applied: $name");
            }

            return Command::SUCCESS;
        } finally {
            $this->pdo->query("SELECT RELEASE_LOCK('" . self::LOCK_NAME . "')");
        }
    }

    /** @return list<string> paths of the files not yet recorded, in name order */
    private function pending(): array
    {
        $files = glob(rtrim($this->directory, '/') . '/*.sql') ?: [];
        sort($files, SORT_STRING);

        $applied = $this->pdo->query('SELECT migration FROM schema_migrations')->fetchAll(PDO::FETCH_COLUMN);
        $applied = array_flip($applied);

        return array_values(array_filter($files, static fn(string $file): bool => !isset($applied[basename($file)])));
    }

    /**
     * The statements in a migration file: each ends with ; at the end of a line.
     * Lines starting with -- and blank lines between statements are dropped.
     *
     * @return list<string>
     */
    public static function statements(string $sql): array
    {
        $statements = [];
        $current = '';
        foreach (preg_split('/\R/', $sql) as $line) {
            $trimmed = trim($line);
            if ($current === '' && ($trimmed === '' || str_starts_with($trimmed, '--'))) {
                continue;
            }
            $current .= $line . "\n";
            if (str_ends_with($trimmed, ';')) {
                $statements[] = trim($current);
                $current = '';
            }
        }
        if (trim($current) !== '') {
            $statements[] = trim($current);
        }
        return $statements;
    }
}
