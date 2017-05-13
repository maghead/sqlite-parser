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
        $data[] = ['CREATE TEMP TABLE `foo` (`a` INT UNSIGNED DEFAULT NULL)', NULL];
        $data[] = ['CREATE TEMP TABLE `foo` (`a` BLOB DEFAULT NULL)', NULL];
        $data[] = ['CREATE TEMP TABLE `foo` (`a` TEXT DEFAULT NULL)', NULL];
        $data[] = ['CREATE TEMP TABLE `foo` (`a` INT DEFAULT -20)', -20];
        $data[] = ['CREATE TEMP TABLE `foo` (`a` VARCHAR NOT NULL DEFAULT \'test\')', 'test'];
        $data[] = ['CREATE TEMP TABLE `foo` (`a` VARCHAR NOT NULL DEFAULT \'t\\\'est\')', 't\\\'est'];
        $data[] = ['CREATE TEMP TABLE `foo` (`a` VARCHAR NOT NULL DEFAULT \'t\\\'est\')', 't\\\'est'];
        $data[] = ['CREATE TEMP TABLE `foo` (`a` TIMESTAMP DEFAULT CURRENT_TIME)', new Token('literal','CURRENT_TIME')];
        $data[] = ['CREATE TEMP TABLE `foo` (`a` TIMESTAMP DEFAULT CURRENT_DATE)', new Token('literal','CURRENT_DATE')];
        $data[] = ['CREATE TEMP TABLE `foo` (`a` TIMESTAMP DEFAULT CURRENT_TIMESTAMP)', new Token('literal','CURRENT_TIMESTAMP')];
        $data[] = ['CREATE TEMP TABLE `foo` (`a` INT DEFAULT -20 CONSTRAINT aa UNIQUE(a))', -20];
        $data[] = ['CREATE TEMP TABLE `foo` (`a` INT DEFAULT -20 CONSTRAINT aa UNIQUE(a ASC))', -20];
        $data[] = ['CREATE TEMP TABLE `foo` (`a` INT DEFAULT -20 CONSTRAINT aa UNIQUE(a DESC))', -20];
        $data[] = ['CREATE TEMP TABLE `foo` (`a` INT DEFAULT -20 CONSTRAINT aa PRIMARY(a))', -20];
        $data[] = ['CREATE TEMP TABLE `foo` (`a` INT DEFAULT -20 CONSTRAINT aa PRIMARY KEY(a))', -20];
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

    public function testForeignKeyReferenceParsing()
    {
        $parser = new CreateTableParser;
        $def = $parser->parse('CREATE TABLE foo (`book_id` INT UNSIGNED NOT NULL CONSTRAINT const_book FOREIGN KEY (book_id) REFERENCES books(id, name))');
        $this->assertNotNull($def);
        $this->assertEquals('foo', $def->tableName);
        $this->assertCount(1, $def->columns);
        $this->assertEquals('book_id', $def->columns[0]->name);
        $this->assertEquals('INT', $def->columns[0]->type);

        $this->assertCount(1, $def->constraints);
        $this->assertInstanceOf('Maghead\\SqliteParser\\Constraint', $def->constraints[0]);
        $this->assertEquals('const_book', $def->constraints[0]->name);

        $this->assertEquals(['book_id'], $def->constraints[0]->foreignKey->columns ); 
        $this->assertEquals('books', $def->constraints[0]->foreignKey->references->table ); 
        $this->assertEquals(['id','name'], $def->constraints[0]->foreignKey->references->columns ); 
    }

    public function testForeignKeyReferenceOnUpdateParsing()
    {
        $sql = 'CREATE TABLE foo (`book_id` INT UNSIGNED NOT NULL 
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
        $this->assertInstanceOf('Maghead\\SqliteParser\\Constraint', $def->constraints[0]);
        $this->assertEquals('const_book', $def->constraints[0]->name);

        $this->assertEquals(['book_id'], $def->constraints[0]->foreignKey->columns ); 
        $this->assertEquals('books', $def->constraints[0]->foreignKey->references->table ); 
        $this->assertEquals(['id'], $def->constraints[0]->foreignKey->references->columns ); 
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
        $def = $parser->parse('CREATE TABLE foo ()');
        $this->assertNotNull($def);
        $this->assertEquals('foo', $def->tableName);
        $this->assertCount(0, $def->columns);
    }

    public function incorrectSqlProvider()
    {
        $data = [];
        $data[] = ['CREATE TABLE foo '];
        $data[] = ['CREATE TABLE foo (`a INT UNSIGNED DEFAULT 123)'];
        $data[] = ['CREATE TABLE foo (`a INT UNSIGNED DEFAULT aaa)']; // invalid default
        $data[] = ['CREATE TABLE foo (`a`)'];
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
