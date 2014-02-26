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

use Doctrine\Common\EventSubscriber;
use JMS\Serializer\Serializer;
use Doctrine\ORM\Events;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use mvrhov\Bundle\DoctrineExtensionsBundle\Doctrine\ValueObjects;

/**
 * short description
 *
 * @author Miha Vrhovnik <miha.vrhovnik@cordia.si>
 */
class ValueObjectListener implements EventSubscriber
{
    private $serializer;

    public function __construct(Serializer $serializer)
    {
        $this->serializer = $serializer;
    }

    /**
     * {@inheritdoc}
     */
    public function getSubscribedEvents()
    {
        return array(Events::prePersist => 'prePersist', Events::preUpdate => 'preUpdate', Events::postLoad => 'postLoad');
    }

    public function preUpdate(PreUpdateEventArgs $event)
    {
        if ($event->getEntity() instanceof ValueObjects) {
            /** @var $entity ValueObjects */
            $entity = $event->getEntity();
            $event->getEntity()->setSerializer($this->serializer);
            $properties = $entity->getValueObjectProperties();
            foreach ($properties as $property => $method) {
                if ($event->hasChangedField($property)) {
                    $event->setNewValue($property, $this->serializer->serialize(call_user_func(array($entity, $method)), 'json'));
                }
            }
        }
    }

    public function prePersist(LifecycleEventArgs $event)
    {
        if ($event->getEntity() instanceof ValueObjects) {
            /** @var $entity ValueObjects */
            $entity = $event->getEntity();
            $event->getEntity()->setSerializer($this->serializer);
            $properties = $entity->getValueObjectProperties();
            $meta = $event->getEntityManager()->getClassMetadata(get_class($entity));

            foreach ($properties as $property => $method) {
                $property = $meta->getReflectionProperty($property);
                $property->setValue($entity, $this->serializer->serialize(call_user_func(array($entity, $method)), 'json'));
            }
        }
    }

    public function postLoad(LifecycleEventArgs $event)
    {
        if ($event->getEntity() instanceof ValueObjects) {
            $event->getEntity()->setSerializer($this->serializer);
        }
    }
}
