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


namespace DownloadApp\Scanners\DeviantArtBundle\Menu;

use DownloadApp\App\FrontendBundle\Menu\MenuGeneratorInterface;
use DownloadApp\Scanners\DeviantArtBundle\Service\ApiProvider;
use Knp\Menu\ItemInterface;

/**
 * Class DeviantartGenerator
 *
 * @package DownloadApp\Scanners\DeviantArtBundle\Menu
 */
class DeviantartGenerator implements MenuGeneratorInterface
{
    /**
     * @var ApiProvider
     */
    private $api;

    /**
     * DeviantartGenerator constructor.
     *
     * @param ApiProvider $api
     */
    public function __construct(ApiProvider $api)
    {
        $this->api = $api;
    }

    /**
     * Generate items for a menu.
     *
     * @param ItemInterface $root
     */
    public function generate(ItemInterface $root)
    {
        if (!$this->api->hasToken()) {
            $root->addChild('-');
            $root->addChild('Authorize DeviantArt', ['route' => 'deviantart_request_authorization']);
        }
    }
}
