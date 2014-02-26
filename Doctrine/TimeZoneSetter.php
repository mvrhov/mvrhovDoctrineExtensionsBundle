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
namespace mvrhov\Bundle\DoctrineExtensionsBundle\Doctrine;

use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\DBAL\Connection;

/**
 * database connection listener
 *
 * @author Miha Vrhovnik <miha.vrhovnik@cordia.si>
 *
 */
class TimeZoneSetter
{
    private $timezone;

    public function __construct($timezone = 'UTC')
    {
        if (empty($timezone)) {
            $timezone = date_default_timezone_get();
        }
        $this->timezone = $timezone;
    }

    /**
     * set the connection's timezone
     * @param \Doctrine\DBAL\Connection $connection
     */
    public function setDatabaseTimezone(Connection $connection)
    {
        $platform = $connection->getDatabasePlatform();

        if ($platform instanceof MySqlPlatform) {
            $tz = new \DateTimeZone($this->timezone);
            $tz = $tz->getOffset(new \DateTime('now', $tz));

            $offset = ($tz < 0) ? '-' : '+';
            $h = $tz / 3600;
            $m = ($tz % 3600) / 60;
            $offset = sprintf($offset . '%02d:%02d', $h, $m);

            $connection->executeUpdate(sprintf("SET time_zone = '%s';", $offset));
        } elseif ($platform instanceof PostgreSqlPlatform) {
            $connection->executeUpdate(sprintf("SET TIME ZONE '%s';", $this->timezone));
        }
    }

    public function setTimezone($timezone)
    {
        $this->timezone = $timezone;

        return $this;
    }

}
