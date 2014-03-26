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
namespace mvrhov\Bundle\DoctrineExtensionsBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * Extension configuration
 *
 * @author Miha Vrhovnik <miha.vrhovnik@cordia.si>
 *
 */
class mvrhovDoctrineExtensionsExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $processor = new Processor();
        $configuration = new Configuration();

        $config = $processor->processConfiguration($configuration, $configs);

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.xml');

        $listenerTpl = 'mvrhov_doctrine_extensions.listener.%s';

        if (isset($config['timezone']) && $config['timezone']) {
            $listener = sprintf($listenerTpl, 'timezone');
            if ($container->hasDefinition($listener)) {
                $definition = $container->getDefinition($listener);
                $definition->addTag(
                    'doctrine.event_listener',
                    array('event' => 'postConnect', 'method' => 'postConnect')
                );
            }
        }
        if (isset($config['value_object']) && $config['value_object']) {
            $listener = sprintf($listenerTpl, 'value_object');
            if ($container->hasDefinition($listener)) {
                $definition = $container->getDefinition($listener);
                $definition->addTag('doctrine.event_subscriber');
            }
        }
        if (isset($config['encrypted']) && $config['encrypted']) {
            $listener = sprintf($listenerTpl, 'encrypted');
            if ($container->hasDefinition($listener)) {
                $definition = $container->getDefinition($listener);
                $definition->addTag('doctrine.event_listener', array('event' => 'postConnect', 'method' => 'postConnect'));
            }
        }
    }
}
