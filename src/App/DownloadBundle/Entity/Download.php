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


namespace DownloadApp\App\DownloadBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use DownloadApp\App\UserBundle\Entity\User;

/**
 * Class Download
 * @package Benkle\DownloadApp\DownloadBundle\Entity
 * @ORM\Entity()
 * @ORM\Table(name="downloads")
 */
class Download
{
    /**
     * @var integer
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(type="string", length=1024, unique=true)
     */
    private $guid;

    /**
     * @var User
     * @ORM\ManyToOne(targetEntity="DownloadApp\App\UserBundle\Entity\User", cascade={"persist", "remove"})
     */
    private $user;

    /**
     * @var string
     * @ORM\Column(type="text", length=65535)
     */
    private $comment = '';

    /**
     * @var \DateTime
     * @ORM\Column(type="datetime", options={"default" = "CURRENT_TIMESTAMP"})
     */
    private $created;

    /**
     * @var bool
     * @ORM\Column(type="boolean", options={"default" = 0})
     */
    private $failed = false;

    /**
     * @var string[]
     * @ORM\Column(type="json_array")
     */
    private $metadata = [];

    /**
     * @var bool
     * @ORM\Column(type="boolean")
     */
    private $downloaded = false;

    /**
     * @var File
     * @ORM\OneToOne(targetEntity="DownloadApp\App\DownloadBundle\Entity\File", cascade={"persist", "remove", "merge"}, orphanRemoval=true, fetch="EAGER")
     */
    private $file;

    /**
     * @var string
     * @ORM\Column(type="text", nullable=true)
     */
    private $error;

    /**
     * Download constructor.
     * @param string|null $guid
     */
    public function __construct($guid = null)
    {
        $this->guid = $guid;
        $this->created = new \DateTime();
    }

    /**
     * @return string
     */
    public function getComment(): string
    {
        return $this->comment;
    }

    /**
     * @param string $comment
     * @return $this
     */
    public function setComment(string $comment): Download
    {
        $this->comment = $comment;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCreated(): \DateTime
    {
        return $this->created;
    }

    /**
     * @param \DateTime $created
     * @return $this
     */
    public function setCreated(\DateTime $created): Download
    {
        $this->created = $created;
        return $this;
    }

    /**
     * @return bool
     */
    public function isFailed(): bool
    {
        return $this->failed;
    }

    /**
     * @param bool $failed
     * @return $this
     */
    public function setFailed(bool $failed): Download
    {
        $this->failed = $failed;
        return $this;
    }

    /**
     * @return \string[]
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    /**
     * @param \string[] $metadata
     * @return $this
     */
    public function setMetadata(array $metadata): Download
    {
        $this->metadata = $metadata;
        return $this;
    }

    public function setMetadatum(string $key, mixed $value): Download
    {
        $this->metadata[$key] = $value;
        return $this;
    }

    /**
     * @return bool
     */
    public function isDownloaded(): bool
    {
        return $this->downloaded;
    }

    /**
     * @param bool $downloaded
     * @return $this
     */
    public function setDownloaded(bool $downloaded): Download
    {
        $this->downloaded = $downloaded;
        return $this;
    }

    /**
     * @return File
     */
    public function getFile(): File
    {
        return $this->file;
    }

    /**
     * @param File $file
     * @return $this
     */
    public function setFile(File $file): Download
    {
        $this->file = $file;
        return $this;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getGuid(): string
    {
        return $this->guid;
    }

    /**
     * @return string
     */
    public function getError(): string
    {
        return $this->error;
    }

    /**
     * @param string $error
     * @return $this
     */
    public function setError($error)
    {
        $this->error = $error;
        return $this;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        $result = [];
        foreach ($this->metadata as $key => $value) {
            $result[] = "$key: $value";
        }
        $result[] = '======================================';
        $result[] = $this->getComment();
        return implode(PHP_EOL, $result);
    }

    /**
     * Set the user.
     *
     * @return User
     */
    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * Get the user.
     *
     * @param User $user
     * @return $this
     */
    public function setUser(User $user): Download
    {
        $this->user = $user;
        return $this;
    }
}
