<?php

namespace Maghead\SqliteParser;

use PHPUnit\Framework\TestCase;
use Exception;

/**
 * @codeCoverageIgnore
 */
class CreateTableParserTest extends TestCase
{
    public function schemaSqlProvider()
    {
        $data = [];
        $data[] = ['CREATE TABLE foo (`a` INT UNSIGNED DEFAULT 123)', 123];
        $data[] = ['CREATE TABLE `foo` (`a` INT UNSIGNED DEFAULT 123)', 123];
        $data[] = ['CREATE TABLE `foo` (`a` INT UNSIGNED PRIMARY DEFAULT 0)', 0];
        $data[] = ['CREATE TABLE `foo` (`a` INT UNSIGNED PRIMARY ASC DEFAULT 0)', 0];
        $data[] = ['CREATE TABLE `foo` (`a` INT UNSIGNED PRIMARY DESC DEFAULT 0)', 0];
        $data[] = ['CREATE TABLE `foo` (`a` INT UNSIGNED PRIMARY AUTOINCREMENT DEFAULT 0)', 0];
        $data[] = ['CREATE TABLE `foo` (`a` INT UNSIGNED UNIQUE DEFAULT 0)', 0];
        $data[] = ['CREATE TABLE IF NOT EXISTS `foo` (`a` INT UNSIGNED DEFAULT 123)', 123];
        $data[] = ['CREATE TEMPORARY TABLE `foo` (`a` INT UNSIGNED DEFAULT 123)', 123];
        $data[] = ['CREATE TEMP TABLE `foo` (`a` INT UNSIGNED DEFAULT 123)', 123];
        $data[] = ['CREATE TEMP TABLE `foo` (`a` BOOLEAN DEFAULT TRUE)', true];
        $data[] = ['CREATE TEMP TABLE `foo` (`a` BOOLEAN DEFAULT FALSE)', false];
        $data[] = ['CREATE TEMP TABLE `foo` (`a` INT UNSIGNED DEFAULT 0)', 0];
        $data[] = ['CREATE TEMP TABLE `foo` (`a` INT UNSIGNED DEFAULT 0.1)', 0.1];
        $data[] = ['CREATE TEMP TABLE `foo` (`a` INT UNSIGNED DEFAULT 1 REFERENCES books(id))', 1];
        $data[] = ['CREATE TEMP TABLE `foo` (`a` DOUBLE DEFAULT 1.222)', 1.222];
        $data[] = ['CREATE TEMP TABLE `foo` (`a` DOUBLE NULL DEFAULT 1.222)', 1.222];
        $data[] = ['CREATE TEMP TABLE `foo` (`a` DECIMAL(10,5) DEFAULT 20)', 20];
        $data[] = ['CREATE TEMP TABLE `foo` (`a` DECIMAL(3) DEFAULT 20)', 20];
        $data[] = ['CREATE TEMP TABLE `foo` (`a` INT UNSIGNED DEFAULT NULL)', null];
        $data[] = ['CREATE TEMP TABLE `foo` (`a` BLOB DEFAULT NULL)', null];
        $data[] = ['CREATE TEMP TABLE `foo` (`a` TEXT DEFAULT NULL)', null];
        $data[] = ['CREATE TEMP TABLE `foo` (`a` INT DEFAULT -20)', -20];
        $data[] = ['CREATE TEMP TABLE `foo` (`a` VARCHAR NOT NULL DEFAULT \'test\')', 'test'];
        $data[] = ['CREATE TEMP TABLE `foo` (`a` VARCHAR NOT NULL DEFAULT \'t\\\'est\')', 't\\\'est'];
        $data[] = ['CREATE TEMP TABLE `foo` (`a` VARCHAR NOT NULL DEFAULT \'t\\\'est\')', 't\\\'est'];
        $data[] = ['CREATE TEMP TABLE `foo` (`a` TIMESTAMP DEFAULT CURRENT_TIME)', new Token('literal', 'CURRENT_TIME')];
        $data[] = ['CREATE TEMP TABLE `foo` (`a` TIMESTAMP DEFAULT CURRENT_DATE)', new Token('literal', 'CURRENT_DATE')];
        $data[] = ['CREATE TEMP TABLE `foo` (`a` TIMESTAMP DEFAULT CURRENT_TIMESTAMP)', new Token('literal', 'CURRENT_TIMESTAMP')];
        $data[] = ['CREATE TEMP TABLE `foo` (`a` INT DEFAULT -20, CONSTRAINT aa UNIQUE(a))', -20];
        $data[] = ['CREATE TEMP TABLE `foo` (`a` INT DEFAULT -20, CONSTRAINT aa UNIQUE(a COLLATE NOCASE ASC))', -20];
        $data[] = ['CREATE TEMP TABLE `foo` (`a` INT DEFAULT -20, CONSTRAINT aa UNIQUE(a ASC))', -20];
        $data[] = ['CREATE TEMP TABLE `foo` (`a` INT DEFAULT -20, CONSTRAINT aa UNIQUE(a DESC))', -20];
        $data[] = ['CREATE TEMP TABLE `foo` (`a` INT DEFAULT -20, CONSTRAINT aa PRIMARY(a))', -20];
        $data[] = ['CREATE TEMP TABLE `foo` (`a` INT DEFAULT -20, CONSTRAINT aa PRIMARY KEY(a))', -20];
        $data[] = ['CREATE TABLE `foo` (`a` TEXT COLLATE NOCASE DEFAULT 0)', 0];
        return $data;
    }

    /**
     * @dataProvider schemaSqlProvider
     */
    public function testDefaultValueParsing($sql, $exp)
    {
        $parser = new CreateTableParser;
        $def = $parser->parse($sql);
        $this->assertObjectHasAttribute('tableName', $def);
        $this->assertEquals('foo', $def->tableName);
        $this->assertCount(1, $def->columns);
        $this->assertEquals($exp, $def->columns[0]->default);
    }

    public function testUniqueIndex()
    {
        $sql = 'CREATE TEMP TABLE `foo` (`a` INT DEFAULT 0, name VARCHAR, address VARCHAR, CONSTRAINT address_idx UNIQUE(name, address))';
        $parser = new CreateTableParser;
        $def = $parser->parse($sql);
        $this->assertCount(3, $def->columns);
        $this->assertCount(1, $def->constraints);
        $this->assertInstanceOf('Maghead\\SqliteParser\\Constraint', $def->constraints[0]);
        $this->assertEquals('address_idx', $def->constraints[0]->name);
        $this->assertCount(2, $def->constraints[0]->unique);
    }



    public function testShouldParseTimestampDefaultCurrentTimestamp()
    {
        $sql = "CREATE TABLE `books` (
            `id` INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
            `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
            `published_at` timestamp
        );";

        $parser = new CreateTableParser;
        $def = $parser->parse($sql);

        $this->assertEquals('updated_at', $def->columns[1]->name);
        $this->assertEquals('TIMESTAMP', $def->columns[1]->type);
        $this->assertNotTrue($def->columns[1]->primary);
        $this->assertNotTrue($def->columns[1]->autoIncrement);
        $this->assertFalse($def->columns[1]->notNull);
        $this->assertInstanceOf(Token::class, $def->columns[1]->default);

        $this->assertEquals('published_at', $def->columns[2]->name);
        $this->assertEquals('TIMESTAMP', $def->columns[2]->type);
        $this->assertNotTrue($def->columns[2]->primary);
        $this->assertNotTrue($def->columns[2]->autoIncrement);
        $this->assertNotTrue($def->columns[2]->notNull);
    }


    public function testShouldParseTimestampNull()
    {
        $sql = "CREATE TABLE `books` (
            `id` INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
            `published_at` timestamp
        );";

        $parser = new CreateTableParser;
        $def = $parser->parse($sql);
        $this->assertEquals('published_at', $def->columns[1]->name);
        $this->assertEquals('TIMESTAMP', $def->columns[1]->type);
        $this->assertNotTrue($def->columns[1]->primary);
        $this->assertNotTrue($def->columns[1]->autoIncrement);
        $this->assertNotTrue($def->columns[1]->notNull);
        $this->assertNotTrue($def->columns[0]->unsigned);
    }


    public function testParseIntegerNotNullPrimaryKeyWithAutoIncrement()
    {
        $sql = "CREATE TABLE `books` (`id` INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT)";
        $parser = new CreateTableParser;
        $def = $parser->parse($sql);
        $this->assertEquals('id', $def->columns[0]->name);
        $this->assertEquals('INTEGER', $def->columns[0]->type);
        $this->assertTrue($def->columns[0]->primary);
        $this->assertTrue($def->columns[0]->autoIncrement);
        $this->assertTrue($def->columns[0]->notNull);
        $this->assertFalse($def->columns[0]->unsigned);
    }


    public function testForeignKeyReferenceParsing()
    {
        $parser = new CreateTableParser;
        $def = $parser->parse('CREATE TABLE foo (`book_id` INT UNSIGNED NOT NULL, CONSTRAINT const_book FOREIGN KEY (book_id) REFERENCES books(id, name))');
        $this->assertNotNull($def);
        $this->assertEquals('foo', $def->tableName);
        $this->assertCount(1, $def->columns);
        $this->assertEquals('book_id', $def->columns[0]->name);
        $this->assertEquals('INT', $def->columns[0]->type);

        $this->assertCount(1, $def->constraints);
        $this->assertInstanceOf('Maghead\\SqliteParser\\Constraint', $def->constraints[0]);
        $this->assertEquals('const_book', $def->constraints[0]->name);

        $this->assertEquals(['book_id'], $def->constraints[0]->foreignKey->columns);
        $this->assertEquals('books', $def->constraints[0]->foreignKey->references->table);
        $this->assertEquals(['id','name'], $def->constraints[0]->foreignKey->references->columns);
    }



    public function testParseReferenceAndDefaultCurrentTimestamp()
    {
        $sql = 'CREATE TABLE `books` (
  `id` INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
  `title` varchar(128),
  `subtitle` varchar(256),
  `isbn` varchar(128) UNIQUE,
  `description` text,
  `view` INTEGER DEFAULT 0,
  `published` boolean DEFAULT 0,
  `publisher_id` INTEGER REFERENCES publishers(id),
  `published_at` timestamp,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NULL,
  `is_hot` boolean DEFAULT 1,
  `is_selled` boolean DEFAULT 0
)';

        $parser = new CreateTableParser;
        $def = $parser->parse($sql);
        $this->assertInstanceOf(Table::class, $def);

        $this->assertEquals(128, $def->columns[1]->length);
        $this->assertEquals(256, $def->columns[2]->length);

        $this->assertInstanceOf(Token::class, $def->columns[9]->default);
        $this->assertEquals('CURRENT_TIMESTAMP', $def->columns[9]->default->val);

        $this->assertEquals('publishers', $def->columns[7]->references->table);
        $this->assertEquals(['id'], $def->columns[7]->references->columns);
    }


    public function testForeignKeyReferenceOnUpdateParsing()
    {
        $sql = 'CREATE TABLE foo (`book_id` INT UNSIGNED NOT NULL,
                CONSTRAINT const_book FOREIGN KEY (book_id) 
                REFERENCES books(id) ON UPDATE SET NULL
        )';
        $parser = new CreateTableParser;
        $def = $parser->parse($sql);
        $this->assertNotNull($def);
        $this->assertEquals('foo', $def->tableName);
        $this->assertCount(1, $def->columns);
        $this->assertEquals('book_id', $def->columns[0]->name);
        $this->assertEquals('INT', $def->columns[0]->type);

        $this->assertCount(1, $def->constraints);
        $this->assertInstanceOf(Constraint::class, $def->constraints[0]);
        $this->assertEquals('const_book', $def->constraints[0]->name);

        $this->assertEquals(['book_id'], $def->constraints[0]->foreignKey->columns);
        $this->assertEquals('books', $def->constraints[0]->foreignKey->references->table);
        $this->assertEquals(['id'], $def->constraints[0]->foreignKey->references->columns);
    }


    public function testUnsignedInt()
    {
        $parser = new CreateTableParser;
        $def = $parser->parse('CREATE TABLE foo (`a` INT UNSIGNED DEFAULT 123)');
        $this->assertNotNull($def);
        $this->assertEquals('foo', $def->tableName);
        $this->assertCount(1, $def->columns);
        $this->assertEquals('a', $def->columns[0]->name);
        $this->assertEquals('INT', $def->columns[0]->type);
        $this->assertEquals("123", $def->columns[0]->default);
    }


    /**
     * @see https://github.com/c9s/Maghead/issues/94
     */
    public function testForIssue94()
    {
        $parser = new CreateTableParser;
        $def = $parser->parse('CREATE TABLE foo (`col4` text DEFAULT \'123\\\'\')');
        $this->assertNotNull($def);
        $this->assertEquals('foo', $def->tableName);
        $this->assertCount(1, $def->columns);

        $this->assertEquals('col4', $def->columns[0]->name);
        $this->assertEquals('TEXT', $def->columns[0]->type);
        $this->assertEquals("123\\'", $def->columns[0]->default);
    }


    public function testEmptyColumns()
    {
        $parser = new CreateTableParser;
        $def = $parser->parse('CREATE TABLE foo ( )');
        $this->assertNotNull($def);
        $this->assertEquals('foo', $def->tableName);
        $this->assertCount(0, $def->columns);
    }

    public function incorrectSqlProvider()
    {
        $data = [];
        $data[] = ['CREATE '];
        $data[] = ['CREATE TABLE foo '];
        $data[] = ['CREATE TABLE foo (`a INT UNSIGNED DEFAULT 123)']; // unbalanced quote
        $data[] = ['CREATE TABLE foo (`a` INT UNSIGNED DEFAULT aaa)']; // invalid default
        $data[] = ['CREATE TABLE foo (`a`)']; // without typename
        $data[] = ['CREATE TABLE foo (`a` INT UNSIGNED DEFAULT 123 CONSTRAINT)'];
        $data[] = ['CREATE TABLE foo (`a` INT UNSIGNED DEFAULT 123 CONSTRAINT aa)'];
        $data[] = ['CREATE TABLE foo (`a` DOUBLE('];
        return $data;
    }

    /**
     * @dataProvider incorrectSqlProvider
     */
    public function testExceptions($sql)
    {
        $this->expectException(\Exception::class);
        $parser = new CreateTableParser;
        $parser->parse($sql);
    }
}
