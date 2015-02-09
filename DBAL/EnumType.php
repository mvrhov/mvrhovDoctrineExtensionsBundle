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
namespace mvrhov\Bundle\DoctrineExtensionsBundle\DBAL;

use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use mvrhov\Type\Enum;

/**
 * Doctrine database type
 *
 * @author Miha Vrhovnik <miha.vrhovnik@cordia.si>
 *
 */
abstract class EnumType extends Type
{

    /**
     * {@inheritDoc}
     */
    public function getSqlDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
    {
        $at = $this->createClass();
        $values = $at->getAll();
        $max = 16;
        foreach ($values as $value) {
            $len = strlen($value);
            $max = $len > $max ? $len : $max;
        }

        if (isset($fieldDeclaration['length']) && $fieldDeclaration['length'] > $max) {
            $max = $fieldDeclaration['length'];
        }
        $platform->markDoctrineTypeCommented($this);

        return $platform->getVarcharTypeDeclarationSQL(array(
            'length' => $max,
        ));
    }

    /**
     * {@inheritDoc}
     */
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        return (null === $value) ? null : $this->createClass($value);
    }

    /**
     * {@inheritDoc}
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        return ($value !== null) ? (string) $value : null;
    }

    /**
     * Create enum class
     *
     * @abstract
     *
     * @param null $value
     *
     * @return Enum
     */
    abstract protected function createClass($value = null);
}
