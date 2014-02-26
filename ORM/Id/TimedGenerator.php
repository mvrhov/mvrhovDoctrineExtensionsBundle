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

/**
 *
 * @author Miha Vrhovnik <miha.vrhovnik@cordia.si>
 *
 */
class TimedGenerator extends AbstractIdGenerator
{

    private $functionName = 'next_id';
    private $sequenceName =  'sequence_generator_seq';
    private $epoch = '1338508800000'; //2012-06-01T00:00:00Z
    private $shardId = 1;

    /**
     * Generates an identifier for an entity.
     *
     * @param EntityManager $em
     * @param object        $entity
     *
     * @return mixed
     */
    public function generate(EntityManager $em, $entity)
    {
        $conn = $em->getConnection();
        $sql  = sprintf('SELECT * FROM %s();', $this->functionName);

        return (int)$conn->fetchColumn($sql);

    }

    public function initializeDatabase(EntityManager $em)
    {
        $conn = $em->getConnection();

        $sequenceSql = "CREATE SEQUENCE {$this->sequenceName};";

        $conn->exec($sequenceSql);

        $functionSql = "CREATE OR REPLACE FUNCTION {$this->functionName}(OUT result bigint) AS $$
        DECLARE
            --this requires that time always moves forward. set tinker setp 0 inside /etc/ntp.conf
            --when running inside xen set xen.independent_wallclock=1 inside /etc/sysctl.conf
            --if this becomes a problem take a look at https://github.com/twitter/snowflake/
            our_epoch bigint := {$this->epoch};
            seq_id bigint;
            now_millis bigint;
            shard_id int := {$this->shardId}; -- 2^10 == 1024 is maximum
        BEGIN
            SELECT nextval('{$this->sequenceName}') % 4096 INTO seq_id;
            SELECT FLOOR(EXTRACT(EPOCH FROM clock_timestamp()) * 1000) INTO now_millis;
            result := (now_millis - our_epoch) << 22; -- 42 bits
            result := result | (shard_id << 12); --10 bits
            result := result | (seq_id); -- 12 bits (4096)
        END;
        $$ LANGUAGE plpgsql;";

        $conn->exec($functionSql);
    }
}
