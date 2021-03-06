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


namespace DownloadApp\App\UtilsBundle\Service;

use PHPUnit\Framework\TestCase;

class PathUtilsTest extends TestCase
{
    public function testJoin()
    {
        $pathUtils = new PathUtils();

        $this->assertEquals('a', $pathUtils->join('a'));
        $this->assertEquals('a/b', $pathUtils->join('a', 'b'));
        $this->assertEquals('a/b', $pathUtils->join('a/', 'b'));
        $this->assertEquals('a/b', $pathUtils->join('a', '/b'));
        $this->assertEquals('a/b/c', $pathUtils->join('a/b', 'c'));
        $this->assertEquals('a/b/c', $pathUtils->join('a/b/', 'c'));
        $this->assertEquals('a/b/c', $pathUtils->join('a/b', '/c'));
        $this->assertEquals('a/b/c', $pathUtils->join('a', 'b/c'));
        $this->assertEquals('a/b/c', $pathUtils->join('a/', 'b/c'));
        $this->assertEquals('a/b/c', $pathUtils->join('a', '/b/c'));
    }

    public function testCleanFilename()
    {
        $pathUtils = new PathUtils();

        $this->assertEquals('a', $pathUtils->cleanFilename('a'));
        $this->assertEquals('a(b)', $pathUtils->cleanFilename('a<b>'));
        $this->assertEquals('a - b', $pathUtils->cleanFilename('a: b'));
        $this->assertEquals('a', $pathUtils->cleanFilename('"a"'));
        $this->assertEquals('a - b', $pathUtils->cleanFilename('a/b'));
        $this->assertEquals('a - b', $pathUtils->cleanFilename('a\\b'));
        $this->assertEquals('a - b', $pathUtils->cleanFilename('a|b'));
        $this->assertEquals('a', $pathUtils->cleanFilename('a?'));
        $this->assertEquals('a', $pathUtils->cleanFilename('a*'));
        $this->assertEquals('a', $pathUtils->cleanFilename("a\x12"));
        $this->assertEquals('a - (b) - c - d - e', $pathUtils->cleanFilename("a\x12*: <b>|\"c\"\\d/e?"));
    }

    public function testSplit()
    {
        $pathUtils = new PathUtils();

        $this->assertEquals(['a', 'b'], $pathUtils->split('a/b'));
        $this->assertEquals(['a', 'b'], $pathUtils->split('/a/b'));
        $this->assertEquals(['a', 'b'], $pathUtils->split('a/b/'));
        $this->assertEquals(['a', 'b'], $pathUtils->split('/a/b/'));

        $this->assertEquals(['a', 'b'], $pathUtils->split('a//b'));
        $this->assertEquals(['a', 'b'], $pathUtils->split('//a/b'));
        $this->assertEquals(['a', 'b'], $pathUtils->split('/a//b'));
        $this->assertEquals(['a', 'b'], $pathUtils->split('a//b/'));
        $this->assertEquals(['a', 'b'], $pathUtils->split('a/b//'));
        $this->assertEquals(['a', 'b'], $pathUtils->split('//a/b/'));
        $this->assertEquals(['a', 'b'], $pathUtils->split('/a//b/'));
        $this->assertEquals(['a', 'b'], $pathUtils->split('/a/b//'));
        $this->assertEquals(['a', 'b'], $pathUtils->split('//a//b//'));
    }
}
