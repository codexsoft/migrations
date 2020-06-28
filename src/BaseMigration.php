<?php

namespace CodexSoft\Migrations;

use CodexSoft\SqlParser\SqlParser;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
abstract class BaseMigration extends AbstractMigration
{

    private const SQL_STATEMENTS_AUTODETECT = 1;
    private const SQL_STATEMENTS_MANUAL = 2;

    protected const SQL_MANUAL_STATEMENT_TOKEN_OPEN = '--@statement';
    protected const SQL_MANUAL_STATEMENT_TOKEN_CLOSE = '--@/statement';

    abstract public static function getContainingDirectory(): string;

    private function strStartsWith(string $haystack, string $needle): bool
    {
        if ($needle === '') {
            return true;
        }

        if ($haystack === '') {
            return false;
        }

        return \strpos($haystack, $needle) === 0;
    }

    protected function getSqlStatements(string $migrationSqlFile): array
    {
        if (!\file_exists($migrationSqlFile)) {
            throw new \RuntimeException('Migration file does not exists! '.$migrationSqlFile);
        }

        $auto_detect_line_endings_prev_value = \ini_get('auto_detect_line_endings');
        \ini_set('auto_detect_line_endings', true);

        $migrationSqlFileHandler = \fopen($migrationSqlFile, 'rb');
        if (!$migrationSqlFileHandler) {
            \ini_set('auto_detect_line_endings', $auto_detect_line_endings_prev_value);
            throw new \RuntimeException('Failed to open migration file '.$migrationSqlFile);
        }

        $buffer = '';
        $statements = [];
        $sqlBufferMode = self::SQL_STATEMENTS_AUTODETECT;

        while (($sqlLine = fgets($migrationSqlFileHandler, 4096)) !== false) {

            if ($sqlLine === '') {
                continue;
            }

            $trimmedSqlLine = \ltrim($sqlLine);

            if ($this->strStartsWith($trimmedSqlLine, static::SQL_MANUAL_STATEMENT_TOKEN_OPEN)) {

                if ($buffer) {
                    if ($sqlBufferMode === self::SQL_STATEMENTS_AUTODETECT) {
                        if (\count($parsedStatements = SqlParser::parseString($buffer))) {
                            \array_push($statements, ...$parsedStatements);
                        }
                    } else {
                        $statements[] = $buffer;
                    }
                    $buffer = '';
                }

                $sqlBufferMode = self::SQL_STATEMENTS_MANUAL;
                continue;
            }

            if ($this->strStartsWith($trimmedSqlLine, static::SQL_MANUAL_STATEMENT_TOKEN_CLOSE)) {
                if ($buffer && ($sqlBufferMode === self::SQL_STATEMENTS_MANUAL)) {
                    $statements[] = $buffer;
                    $buffer = '';
                }

                $sqlBufferMode = self::SQL_STATEMENTS_AUTODETECT;
                continue;
            }

            if ($this->strStartsWith($trimmedSqlLine, '--')) {
                continue;
            }

            $buffer .= $sqlLine;
        }

        if ($buffer) {
            if ($sqlBufferMode === self::SQL_STATEMENTS_AUTODETECT) {
                if (\count($parsedStatements = SqlParser::parseString($buffer))) {
                    \array_push($statements, ...$parsedStatements);
                }
            } else {
                $statements[] = $buffer;
            }
        }

        if (!feof($migrationSqlFileHandler)) {
            throw new \RuntimeException('Migration file probably has problems with reading EOF! '.$migrationSqlFile);
        }

        \fclose($migrationSqlFileHandler);

        \ini_set('auto_detect_line_endings', $auto_detect_line_endings_prev_value);
        return $statements;
    }

    /**
     * Of course, instead we can override up() method and use Schema $schema.
     * @param Schema $schema
     *
     * @throws \Exception
     */
    public function up(Schema $schema): void
    {
        $platform = $this->connection->getDatabasePlatform();
        $this->abortIf(!$platform || ($platform->getName() !== 'postgresql'), 'Migration can only be executed safely on \'postgresql\'.');

        $migrationSqlFile = static::getContainingDirectory().'/'.$this->getShortClass(static::class).'.sql';
        $statemets = $this->getSqlStatements($migrationSqlFile);

        $this->addSql('BEGIN');
        foreach($statemets as $statement) {
            $this->addSql($statement);
        }
        $this->addSql('COMMIT');
    }

    private function getShortClass(string $namespacedClass)
    {
        return (new \ReflectionClass($namespacedClass))->getShortName();
    }

    /**
     * Of course, instead we can override down() method and use Schema $schema.
     * @param Schema $schema
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    public function down(Schema $schema): void
    {
        $platform = $this->connection->getDatabasePlatform();
        $this->abortIf(!$platform || ($platform->getName() !== 'postgresql'), 'Migration can only be executed safely on \'postgresql\'.');

        $migrationSqlFile = static::getContainingDirectory().'/'.$this->getShortClass(static::class).'down.sql';
        if (!\file_exists($migrationSqlFile)) {
            $this->throwIrreversibleMigrationException();
        }
        $statemets = $this->getSqlStatements($migrationSqlFile);

        $this->addSql('BEGIN');
        foreach($statemets as $statement) {
            $this->addSql($statement);
        }
        $this->addSql('COMMIT');
    }

}
