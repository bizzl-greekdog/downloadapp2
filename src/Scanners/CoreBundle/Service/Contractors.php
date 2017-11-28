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


namespace DownloadApp\Scanners\CoreBundle\Service;

use DownloadApp\Scanners\CoreBundle\Contractor\ContractorInterface;

/**
 * Class Contractors
 * @package DownloadApp\Scanners\CoreBundle\Service
 */
class Contractors implements \Iterator, ContractorInterface
{
    /** @var \SplPriorityQueue */
    private $contractors;

    /**
     * Contractors constructor.
     */
    public function __construct()
    {
        $this->contractors = new \SplPriorityQueue();
    }

    /**
     * Add a contractor.
     *
     * @param ContractorInterface $contractor
     * @param int $priority
     */
    public function add(ContractorInterface $contractor, int $priority = 10)
    {
        $this->contractors->insert($contractor, $priority);
    }

    /**
     * Return the current element
     * @link http://php.net/manual/en/iterator.current.php
     * @return ContractorInterface Can return any type.
     * @since 5.0.0
     */
    public function current(): ContractorInterface
    {
        return $this->contractors->current();
    }

    /**
     * Move forward to next element
     * @link http://php.net/manual/en/iterator.next.php
     * @return void Any returned value is ignored.
     * @since 5.0.0
     */
    public function next()
    {
        $this->contractors->next();
    }

    /**
     * Return the key of the current element
     * @link http://php.net/manual/en/iterator.key.php
     * @return mixed scalar on success, or null on failure.
     * @since 5.0.0
     */
    public function key()
    {
        $this->contractors->key();
    }

    /**
     * Checks if current position is valid
     * @link http://php.net/manual/en/iterator.valid.php
     * @return boolean The return value will be casted to boolean and then evaluated.
     * Returns true on success or false on failure.
     * @since 5.0.0
     */
    public function valid()
    {
        return $this->contractors->valid();
    }

    /**
     * Rewind the Iterator to the first element
     * @link http://php.net/manual/en/iterator.rewind.php
     * @return void Any returned value is ignored.
     * @since 5.0.0
     */
    public function rewind()
    {
        $this->contractors->rewind();
    }

    /**
     * Contract a scan job.
     *
     * @param string $url
     * @param string|null $referer
     * @return bool
     */
    public function contract(string $url, string $referer = null): bool
    {
        foreach ($this as $contractor) {
            if ($contractor->contract($url, $referer)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Contract a watchlist scan.
     */
    public function contractWatchlist()
    {
        foreach ($this as $contractor) {
            $contractor->contractWatchlist();
        }
    }
}
