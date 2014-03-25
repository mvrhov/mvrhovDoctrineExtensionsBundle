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
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Platforms\AbstractPlatform;

/**
 * Doctrine database type
 *
 * @author Miha Vrhovnik <miha.vrhovnik@cordia.si>
 *
 */
class EncryptedStringType extends Type
{

    const __name = 'encryptedString';

    /** @var string */
    protected $key;

    /** @var  */
    protected $module;

    public function setKey($key)
    {
        $this->key = $key;
    }

    /**
     * {@inheritDoc}
     */
    public function getSqlDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
    {
        $platform->markDoctrineTypeCommented($this);

        return $platform->getClobTypeDeclarationSQL($fieldDeclaration);
    }

    /**
     * {@inheritDoc}
     */
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        $value = (is_resource($value)) ? stream_get_contents($value) : $value;

        if (false === $comma = strpos($value, ',')) {
            return null;
        }

        list($iv, $value) = explode(',', $value);

        return $this->decrypt(base64_decode($value), base64_decode($iv));
    }

    /**
     * {@inheritDoc}
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        $iv = '';
        $value = $this->encrypt($value, $iv);

        return rtrim(base64_encode($iv), '=') . ',' .
               rtrim(base64_encode($value), '=');
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return self::__name;
    }

    protected function encrypt($data, &$iv)
    {
        if (null === $this->module) {
            $this->module = mcrypt_module_open('rijndael-256', '', 'cfb', '');
        }

        $iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($this->module), MCRYPT_DEV_URANDOM);
        $keyLen = mcrypt_enc_get_key_size($this->module);

        if (strlen($this->key) > $keyLen) {
            $key = substr($this->key, 0, $keyLen);
        } else {
            $key = $this->key;
        }
        mcrypt_generic_init($this->module, $key, $iv);
        $data = mcrypt_generic($this->module, $data);
        mcrypt_generic_deinit($this->module);

        return $data;
    }

    protected function decrypt($data, $iv)
    {
        if ('' == $data || '' == $iv) {
            return '';
        }

        if (null === $this->module) {
            $this->module = mcrypt_module_open('rijndael-256', '', 'cfb', '');
        }

        $keyLen = mcrypt_enc_get_key_size($this->module);
        if (strlen($this->key) > $keyLen) {
            $key = substr($this->key, 0, $keyLen);
        } else {
            $key = $this->key;
        }

        mcrypt_generic_init($this->module, $key, $iv);
        $data = mdecrypt_generic($this->module, $data);
        mcrypt_generic_deinit($this->module);

        return rtrim($data, "\0");
    }
}
