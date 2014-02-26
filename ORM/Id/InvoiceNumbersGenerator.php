<?php
/**
 * Released under the MIT License.
 *
 * Copyright (c) 2012 Miha Vrhovnik <miha.vrhovnik@cordia.si>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a
 * copy of this software and associated documentation files (the "Software"),
 * to deal in the Software without restriction, including without limitation
 * the rights to use, copy, modify, merge, publish, distribute, sublicense,
 * and/or sell copies of the Software, and to permit persons to whom the
 * Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included
 * in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */
namespace mvrhov\Bundle\DoctrineExtensionsBundle\ORM\Id;

use Doctrine\ORM\Id\AbstractIdGenerator;
use Doctrine\ORM\EntityManager;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Schema\Schema;

/**
 *
 * @author Miha Vrhovnik <miha.vrhovnik@cordia.si>
 *
 */
class InvoiceNumbersGenerator extends AbstractIdGenerator
{

    private $tableName = 'invoice_number_generator';

    /**
     * Generates an identifier for an entity.
     *
     * @param EntityManager $em
     * @param               $entity
     * @return mixed
     */
    public function generate(EntityManager $em, $entity)
    {
        $conn = $em->getConnection();
        $now = new \DateTime();
        $year = $now->format('Y');
        $padding = 5;

        $stmt = $conn->query(sprintf('SELECT * FROM next_invoice_id(\'%s\', %s);', $year, $padding));
        if (false === ($value = $stmt->fetchColumn(0))) {
            throw new \LogicException('Unable to generate identifier.');
        }

        return $value;
    }

    /**
     * Initializes caching tables
     *
     * @param EntityManager $em
     *
     * @return bool
     */
    public function initializeDatabase(EntityManager $em)
    {
        $conn = $em->getConnection();

        $sm = $conn->getSchemaManager();
        if (in_array($this->tableName, $sm->listTableNames())) {
            return true;
        }

        //no table with such name exists.. Create it
        $schema = new Schema();

        $schema->createTable($this->tableName);
        $table = $schema->getTable($this->tableName);

        $table->addColumn('prefix', 'string', array('length' => 32));
        $table->addColumn('counter', 'integer');
        $table->addUniqueIndex(array('prefix'));

        $sm->createTable($table);

        $function = "
        CREATE OR REPLACE FUNCTION next_invoice_id(prefix_ varchar(32), paddingLength int) RETURNS varchar AS $$
        DECLARE
          newCounter integer;
        BEGIN
          LOOP
            UPDATE {$this->tableName} SET counter = counter + 1 WHERE prefix = prefix_ RETURNING counter INTO newCounter;
            IF found THEN
              return prefix_ || '-' || lpad(newCounter::varchar, paddingLength, '0');
            END IF;

            -- update failed, try to insert
            BEGIN
              INSERT INTO {$this->tableName} (prefix, counter) VALUES (prefix_, 1);
              return prefix_ || '-' || lpad('1', paddingLength, '0');
            EXCEPTION WHEN unique_violation THEN
              -- someone inserted while we were executing
            END;
          END LOOP;
        END;
        $$ LANGUAGE plpgsql;";

        return $conn->exec($function);
    }
}
