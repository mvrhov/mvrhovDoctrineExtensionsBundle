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
class DailyGenerator extends AbstractIdGenerator
{

    private $tableName = 'daily_generator';

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
        $numPart = strtoupper($now->format('y') . base_convert($now->format('m'), 10, 16) . base_convert($now->format('d'), 10, 36));

        $alpha = function ($length) {
            $ret = '';
            for ($i = 0; $i < $length; $i++) {
                $ret .= mt_rand(0, 3) > 0 ? chr(mt_rand(65, 90)) /* A-Z */ : chr(mt_rand(50, 57)) /* 2-9 */;
            }

            return $ret;
        };

        while (true) {
            try {
                $id =  $alpha(3) . '-' . $alpha(3) . '-' . $numPart;

                $conn->insert($this->tableName, array(
                    'id' => $id,
                    'created_at' => $now->format('Ymd'),
                ));

                return $id;
            } catch (DBALException $e) {
                //23505 == Unique violation
                if (23505 != $e->getPrevious()->getCode()) {
                    throw $e;
                }
            }
        }

        throw new \LogicException('Unable to generate identifier.');
    }

    /**
     * Removes the old items from caching table
     * @param EntityManager $em
     * @param int           $days
     *
     * @return int
     * @throws \InvalidArgumentException
     */
    public function cleanup(EntityManager $em, $days = 2)
    {
        if (!ctype_digit((string)$days)) {
            throw new \InvalidArgumentException(sprintf('%s is not a valid number.', $days));
        }

        $otherDay = new \DateTime("now - $days days");
        $conn = $em->getConnection();

        $st = $conn->executeQuery("DELETE FROM {$this->tableName} WHERE created_at <= :created", array('created' => $otherDay->format('Ymd')));

        return $st->rowCount();
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

        $table->addColumn('id', 'string', array('length' => 16));
        $table->addColumn('created_at', 'integer');

        $table->addIndex(array('created_at'));
        $table->addUniqueIndex(array('id'));

        $sm->createTable($table);

        return true;
    }
}
