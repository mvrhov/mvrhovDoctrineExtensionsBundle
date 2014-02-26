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

use Doctrine\DBAL\Event\ConnectionEventArgs;
use Doctrine\DBAL\Events;
use Doctrine\Common\EventSubscriber;

/**
 * database connection listener
 *
 * @author Miha Vrhovnik <miha.vrhovnik@cordia.si>
 *
 */
class Encrypted implements EventSubscriber
{

    private $encryptionKey;

    public function __construct($encryptionKey)
    {
        $this->encryptionKey = $encryptionKey;
    }
    /**
     * @param ConnectionEventArgs $args
     * @return void
     */
    public function postConnect(ConnectionEventArgs $args)
    {
        $types = \Doctrine\DBAL\Types\Type::getTypesMap();

        if (isset($types['encryptedString'])) {
            $t = \Doctrine\DBAL\Types\Type::getType('encryptedString');
            $t->setKey($this->encryptionKey);
        } elseif (isset($types['encryptedJson'])) {
            $t = \Doctrine\DBAL\Types\Type::getType('encryptedJson');
            $t->setKey($this->encryptionKey);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getSubscribedEvents()
    {
        return array(Events::postConnect);
    }
}