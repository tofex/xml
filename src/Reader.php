<?php

namespace Tofex\Xml;

use Exception;
use Tofex\Help\Arrays;
use Tofex\Help\Files;

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2022 Tofex UG (http://www.tofex.de)
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Reader
{
    /** @var Files */
    protected $fileHelper;

    /** @var Arrays */
    protected $arrayHelper;

    /** @var SimpleXml */
    protected $simpleXml;

    /** @var string */
    private $basePath = './';

    /** @var string */
    private $fileName;

    /**
     * @param Files     $fileHelper
     * @param Arrays    $arrayHelper
     * @param SimpleXml $simpleXml
     */
    public function __construct(Files $fileHelper, Arrays $arrayHelper, SimpleXml $simpleXml)
    {
        $this->fileHelper = $fileHelper;
        $this->arrayHelper = $arrayHelper;

        $this->simpleXml = $simpleXml;
    }

    /**
     * @return string
     */
    public function getBasePath(): string
    {
        return $this->basePath;
    }

    /**
     * @param string $basePath
     */
    public function setBasePath(string $basePath): void
    {
        $this->basePath = $basePath;
    }

    /**
     * @return string
     */
    public function getFileName(): string
    {
        return $this->fileName;
    }

    /**
     * @param string $fileName
     */
    public function setFileName(string $fileName)
    {
        $this->fileName = $fileName;
    }

    /**
     * Method to read data from XML file. The retry pause has to be defined in milliseconds.
     *
     * @param bool $removeEmptyElements
     * @param int  $retries
     * @param int  $retryPause
     *
     * @return array
     * @throws Exception
     */
    public function read(
        bool $removeEmptyElements = true,
        int $retries = 0,
        int $retryPause = 250)
    {
        $fileName = $this->fileHelper->determineFilePath($this->getFileName(), $this->getBasePath());

        if (is_file($fileName)) {
            $data = $this->simpleXml->simpleXmlLoadFile($fileName, $retries, $retryPause);

            if ($data !== false) {
                $data = json_decode(json_encode((array)$data), true);

                if ($removeEmptyElements) {
                    $data = $this->arrayHelper->arrayFilterRecursive($data);
                }
            }

            return $data;
        } else {
            throw new Exception(sprintf('Could not read file: %s because: Not a file', $fileName));
        }
    }
}
