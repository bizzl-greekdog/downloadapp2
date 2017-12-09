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


namespace DownloadApp\App\DownloadBundle\Service;

use DownloadApp\App\DownloadBundle\Entity\Download;

/**
 * Class DownloadFormatter
 * @package DownloadApp\App\DownloadBundle\Service
 */
class Formatter extends \Twig_Extension
{
    /**
     * Get twig filter.
     *
     * @return \Twig_Filter[]
     */
    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter('formatDownload', [$this, 'format']),
            new \Twig_SimpleFilter('titleDownload', [$this, 'title'], ['is_safe' => ['html']]),
        ];
    }

    /**
     * Cleaner for comment texts.
     *
     * @param string $text
     * @return string
     */
    private function cleanText(string $text): string
    {
        $text = preg_replace('/[\t\f\r]/', '', $text);
        $text = preg_replace('/<br[^>]*>/', PHP_EOL, $text);
        $text = preg_replace('/<a[^>]*iconusername[^>]*>[^<]*<img[^>]*title="([^"]+)"[^>]*>[^<]*<\/a>/', '$1', $text);
        $text = preg_replace('/<(\/?[bisu])(?: [^>]*)?>/', '[$1]', $text);
        $text = preg_replace('/<[^>]+>/', '', $text);
        $text = preg_replace('/^[\t\n\f\r]*|[\t\n\f\r]*$/', '', $text);
        $text = preg_replace("/( *\n){3,}/", PHP_EOL . PHP_EOL, $text);
        $text = preg_replace('/;([^ ])/', '; $1', $text);
        $text = preg_replace('/^\n+/', '', $text);
        $text = preg_replace('/\[(\/?[bisu])]/', '<$1>', $text);
        $text = preg_replace('/&nbsp;/', ' ', $text);

        $text = explode(PHP_EOL, $text);
        $text = array_map('trim', $text);
        $text = implode(PHP_EOL, $text);

        $text = trim($text, "\n\r \t");

        return $text;
    }

    /**
     * Format metadata to be saved next to the downloaded file.
     *
     * @param Download $download
     * @return string
     */
    public function format(Download $download): string
    {
        $result = [];
        foreach ($download->getMetadata() as $key => $value) {
            $result[] = "$key: $value";
        }
        $result[] = '======================================';
        $result[] = $this->cleanText($download->getComment());
        return implode(PHP_EOL, $result);
    }

    public function title(Download $download): string
    {
        $metadata = $download->getMetadata();
        if (isset($metadata['Title']) && isset($metadata['Artist'])) {
            return "<em>{$metadata['Title']}</em> by <em>{$metadata['Artist']}</em>";
        } elseif (isset($metadata['Title'])) {
            return "<em>{$metadata['Title']}</em>";
        } else {
            return "<em>{$download->getFile()->getFilename()}</em>";
        }
    }
}
