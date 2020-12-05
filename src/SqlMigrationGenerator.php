<?php


namespace CodexSoft\Migrations;


use Doctrine\Migrations\Generator\Generator;

class SqlMigrationGenerator extends Generator
{
    public function generateMigration(string $fqcn, ?string $up = null, ?string $down = null): string
    {
        $path = parent::generateMigration($fqcn, $up, $down);

        \file_put_contents(BaseMigration::generateUpSqlPathByMigrationPhpFile($path), '-- write UP migration SQL code here');
        \file_put_contents(BaseMigration::generateDownSqlPathByMigrationPhpFile($path), '-- write DOWN migration SQL code here (it should completely revert UP migration)');

        return $path;
    }
}
