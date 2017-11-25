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

/**
 * Class PathUtilsService
 * @package DownloadApp\App\UtilsBundle\Service
 */
class PathUtilsService
{
    private $cleanFilenamePatterns = [
        '/[<]/'           => '(',
        '/[>]/'           => ')',
        '/[ ]?:[ ]?/'     => ' - ',
        '/"/'             => '',
        '/[ ]?\/[ ]?/'    => ' - ',
        '/[ ]?\\\\[ ]?/'  => ' - ',
        '/[ ]?\|[ ]?/'    => ' - ',
        '/[?]/'           => '',
        '/[*]/'           => '',
        '/[\x00-\x1f]/'   => '',
    ];

    /**
     * Clean up a filename so it will be safe for windows.
     *
     * @param string $filename
     * @return string
     */
    public function cleanFilename(string $filename): string
    {
        return preg_replace(
            array_keys($this->cleanFilenamePatterns),
            array_values($this->cleanFilenamePatterns),
            $filename
        );
    }

    /**
     * Join elements into a coherent path.
     *
     * @param \string[] ...$parts
     * @return string
     */
    public function join(string ...$parts): string
    {
        $result = array_shift($parts);
        foreach ($parts as $part) {
            $result = rtrim($result, '/');
            $part = ltrim($part, '/');
            $result .= '/' . $part;
        }
        return $result;
    }
}
