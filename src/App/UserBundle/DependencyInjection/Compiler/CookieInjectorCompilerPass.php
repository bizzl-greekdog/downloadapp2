<?php
/*
 * Copyright (c) 2017 Benjamin Kleiner
 *
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.  IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

namespace DownloadApp\App\UserBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Class CookieInjectorCompilerPass
 * @package DownloadApp\App\UserBundle\DependencyInjection\Compiler
 */
class CookieInjectorCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $prefix = 'csa_guzzle.client.';
        $prefixLength = strlen($prefix);
        $definitions = $container->getDefinitions();
        foreach ($definitions as $name => $definition) {
            if (substr($name, 0, $prefixLength) == $prefix) {
                $options = $definition->getArgument(0);
                if (isset($options['cookies']) && substr($options['cookies'], 0, 1) == '@') {
                    $options['cookies'] = new Reference(substr($options['cookies'], 1));
                }
                $definition->setArgument(0, $options);
            }
        }
    }
}
