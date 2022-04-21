<?php

namespace Tofex\Xml;

use Exception;
use Tofex\Help\Arrays;
use Tofex\Help\Files;
use XMLWriter;

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2022 Tofex UG (http://www.tofex.de)
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Writer
{
    /** Define regular expression to identify data that needs CDATA */
    const CDATA_REGEX = '/[^a-zA-Z0-9-_.,:;# \/]/';

    /** @var Files */
    protected $fileHelper;

    /** @var Arrays */
    protected $arrayHelper;

    /** @var string */
    private $basePath = './';

    /** @var string */
    private $fileName;

    /** @var int */
    private $flushCounter = 0;

    /** @var array */
    private $forceCharacterData = [];

    /**
     * @param Files           $fileHelper
     * @param Arrays          $arrayHelper
     */
    public function __construct(Files $fileHelper, Arrays $arrayHelper)
    {
        $this->fileHelper = $fileHelper;
        $this->arrayHelper = $arrayHelper;
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
     * @return array
     */
    public function getForceCharacterData(): array
    {
        return $this->forceCharacterData;
    }

    /**
     * @param string $elementName
     */
    public function addForceCharacterData(string $elementName)
    {
        $this->forceCharacterData[] = $elementName;
    }

    /**
     * @param string $rootElement
     * @param array  $rootElementAttributes
     * @param array  $data
     * @param bool   $append
     * @param string $version
     * @param string $encoding
     *
     * @throws Exception
     */
    public function write(
        string $rootElement,
        array $rootElementAttributes,
        array $data,
        bool $append = false,
        string $version = '1.0',
        string $encoding = 'UTF-8')
    {
        $fileName = $this->fileHelper->determineFilePath($this->getFileName(), $this->getBasePath());

        if ( ! $append && file_exists($fileName)) {
            unlink($fileName);
        }

        $this->fileHelper->createDirectory(dirname($fileName));

        $xmlWriter = new XMLWriter();
        $xmlWriter->openMemory();
        $xmlWriter->setIndent(true);
        $xmlWriter->setIndentString('  ');
        $xmlWriter->startDocument($version, $encoding);

        $xmlWriter->startElement($rootElement);

        foreach ($rootElementAttributes as $rootElementAttributeName => $rootElementAttributeValue) {
            $xmlWriter->writeAttribute($rootElementAttributeName, $rootElementAttributeValue);
        }

        file_put_contents($fileName, $xmlWriter->flush(true));

        $this->flushCounter = 0;

        foreach ($data as $key => $value) {
            $this->addElement($xmlWriter, $key, $value);
        }

        $xmlWriter->endElement();

        file_put_contents($fileName, $xmlWriter->flush(true), FILE_APPEND);
    }

    /**
     * Add xml-node with optional data
     *
     * @param XMLWriter $xmlWriter
     * @param string     $name
     * @param mixed      $data
     * @param array      $attributes
     */
    protected function addElement(XMLWriter $xmlWriter, string $name, $data = null, array $attributes = [])
    {
        if (is_array($data)) {
            if ($this->arrayHelper->isAssociative($data)) {
                $xmlWriter->startElement($name);
                foreach ($attributes as $attributeName => $attributeValue) {
                    $xmlWriter->writeAttribute($attributeName, $attributeValue);
                }
                foreach ($data as $key => $value) {
                    $this->addElement($xmlWriter, $key, $value);
                }
                $xmlWriter->endElement();
            } else {
                foreach ($data as $value) {
                    $this->addElement($xmlWriter, $name, $value, $attributes);
                }
            }
        } else {
            $this->writeData($xmlWriter, $name, $data);
        }
    }

    /**
     * @param XMLWriter $xmlWriter
     * @param string     $name
     * @param string     $data
     * @param array      $attributes
     */
    protected function writeData(XMLWriter $xmlWriter, string $name, string $data, array $attributes = [])
    {
        $isCharacterData = array_search($name, $this->forceCharacterData) !== false;

        if ($isCharacterData || preg_match(static::CDATA_REGEX, $data, $matches)) {
            $xmlWriter->startElement($name);
            $xmlWriter->writeCdata($this->encode($data));
            $xmlWriter->endElement();
        } else {
            if (empty($attributes)) {
                $xmlWriter->writeElement($name, $this->encode($data));
            } else {
                $xmlWriter->startElement($name);
                foreach ($attributes as $attributeName => $attributeValue) {
                    $xmlWriter->writeAttribute($attributeName, $attributeValue);
                }
                $xmlWriter->text($data);
                $xmlWriter->endElement();
            }
        }

        $this->flushCounter++;

        if ($this->flushCounter === 1000) {
            file_put_contents($this->getFileName(), $xmlWriter->flush(true), FILE_APPEND);

            $this->flushCounter = 0;
        }
    }

    /**
     * @param string $text
     * @param string $charset
     *
     * @return string
     */
    protected function encode(string $text, string $charset = 'UTF-8'): string
    {
        if (function_exists('iconv') && function_exists('mb_detect_encoding') &&
            function_exists('mb_detect_order')) {

            $text = iconv(mb_detect_encoding($text, mb_detect_order(), true), $charset, $text);
        }

        return $text;
    }
}
