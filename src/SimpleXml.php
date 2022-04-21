<?php

namespace Tofex\Xml;

use Exception;
use LibXMLError;
use SimpleXMLElement;
use stdClass;
use Tofex\Help\Variables;

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2022 Tofex UG (http://www.tofex.de)
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class SimpleXml
{
    /** @var Variables */
    protected $variableHelper;

    /**
     * @param Variables $variableHelper
     */
    public function __construct(Variables $variableHelper)
    {
        $this->variableHelper = $variableHelper;
    }

    /**
     * @param string $content
     *
     * @return SimpleXMLElement
     * @throws Exception
     */
    public function simpleXmlLoadString(string $content)
    {
        $useErrors = libxml_use_internal_errors(true);

        $xml = simplexml_load_string($content, 'SimpleXMLElement', LIBXML_NOCDATA);

        $error = null;

        if (false === $xml) {
            $errors = libxml_get_errors();

            $error = reset($errors);
        }

        libxml_clear_errors();
        libxml_use_internal_errors($useErrors);

        if ($error !== null) {
            throw new Exception($error instanceof stdClass ? $this->formatXmlError($error, explode("\n", $content)) :
                $this->formatLibXmlError($error, explode("\n", $content)));
        }

        return $xml;
    }

    /**
     * @param string $fileName
     * @param int    $retries
     * @param int    $retryPause
     *
     * @return SimpleXMLElement|false
     * @throws Exception
     */
    public function simpleXmlLoadFile(
        string $fileName,
        int $retries = 0,
        int $retryPause = 250)
    {
        $useErrors = libxml_use_internal_errors(true);

        $counter = 0;

        do {
            $counter++;

            libxml_clear_errors();
            libxml_use_internal_errors(true);

            $xml = simplexml_load_file($fileName, 'SimpleXMLElement', LIBXML_NOCDATA);

            $error = null;

            if (false === $xml) {
                $errors = libxml_get_errors();

                $error = reset($errors);
            }

            libxml_use_internal_errors($useErrors);

            if (false === $xml) {
                if ($counter > $retries) {
                    throw new Exception(sprintf('Could not read file: %s because: %s', $fileName,
                        $this->variableHelper->isEmpty($error) ? 'Could not parse XML' : ($error instanceof stdClass ?
                            $this->formatXmlError($error, file($fileName, FILE_IGNORE_NEW_LINES)) :
                            $this->formatLibXmlError($error, file($fileName, FILE_IGNORE_NEW_LINES)))));
                } else {
                    usleep($retryPause * 1000);
                }
            } else {
                break;
            }
        } while (true);

        return $xml;
    }

    /**
     * @param stdClass $error
     * @param array    $content
     *
     * @return string
     */
    protected function formatXmlError(stdClass $error, array $content): string
    {
        $return = '';

        if (array_key_exists($error->line - 1, $content)) {
            $return .= $content[ $error->line - 1 ] . "\n";
            $return .= str_repeat('-', $error->column) . "^\n";
        }

        switch ($error->level) {
            case LIBXML_ERR_WARNING:
                $return .= "Warning $error->code: ";
                break;
            case LIBXML_ERR_ERROR:
                $return .= "Error $error->code: ";
                break;
            case LIBXML_ERR_FATAL:
                $return .= "Fatal Error $error->code: ";
                break;
        }

        $return .= trim($error->message) . "\n  Line: $error->line" . "\n  Column: $error->column";

        return $return;
    }

    /**
     * @param LibXMLError $error
     * @param array       $content
     *
     * @return string
     */
    protected function formatLibXmlError(LibXMLError $error, array $content): string
    {
        $return = '';

        if (array_key_exists($error->line - 1, $content)) {
            $return .= $content[ $error->line - 1 ] . "\n";
            $return .= str_repeat('-', $error->column) . "^\n";
        }

        switch ($error->level) {
            case LIBXML_ERR_WARNING:
                $return .= "Warning $error->code: ";
                break;
            case LIBXML_ERR_ERROR:
                $return .= "Error $error->code: ";
                break;
            case LIBXML_ERR_FATAL:
                $return .= "Fatal Error $error->code: ";
                break;
        }

        $return .= trim($error->message) . "\n  Line: $error->line" . "\n  Column: $error->column";

        return $return;
    }

    /**
     * @param SimpleXMLElement $xml
     *
     * @return array
     */
    public function xmlToArray(SimpleXMLElement $xml): array
    {
        return json_decode(json_encode((array)$xml), true);
    }
}
