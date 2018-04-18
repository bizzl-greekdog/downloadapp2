<?php
/*
 * Copyright (c) 2018 Benjamin Kleiner
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


namespace DownloadApp\App\FrontendBundle\Menu;


use Knp\Bundle\MenuBundle\Tests\Stubs\Menu\ContainerAwareBuilder;
use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;

class MenuBuilder extends ContainerAwareBuilder
{
    /**
     * @var \SplPriorityQueue
     */
    private $generators;

    /**
     * @var string
     */
    private $title;

    /**
     * @var FactoryInterface
     */
    private $factory;

    /**
     * MenuBuilder constructor.
     *
     * @param string $title
     * @param FactoryInterface $factory
     */
    public function __construct(string $title, FactoryInterface $factory)
    {
        $this->generators = new \SplPriorityQueue();
        $this->title = $title;
        $this->factory = $factory;
    }


    /**
     * Generate the menu.
     *
     * @param FactoryInterface $factory
     * @param array $options
     * @return ItemInterface
     */
    public function generateMenu(array $options): ItemInterface
    {
        $menu = $this->factory->createItem('root', $options);
        $menu->setLabel($this->title);

        /** @var MenuGeneratorInterface $generator */
        foreach ($this->generators as $generator) {
            $generator->generate($menu);
        }

        return $menu;
    }

    /**
     * Add a menu generator.
     *
     * @param MenuGeneratorInterface $generator
     * @param int $priority
     */
    public function addGenerator(MenuGeneratorInterface $generator, int $priority = 10)
    {
        $this->generators->insert($generator, $priority);
    }
}
