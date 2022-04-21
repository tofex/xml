<?php

namespace Tofex\Xml;

use DOMNode;
use Tofex\Help\Arrays;

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2022 Tofex UG (http://www.tofex.de)
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class DOMDocument
{
    /** @var Arrays */
    protected $arrayHelper;

    /**
     * @param Arrays $arrayHelper
     */
    public function __construct(Arrays $arrayHelper)
    {
        $this->arrayHelper = $arrayHelper;
    }

    /**
     * @param array        $config
     * @param \DOMDocument $document
     * @param DOMNode      $node
     */
    public function arrayToXml(array $config, \DOMDocument $document, DOMNode $node)
    {
        if (array_key_exists('@attributes', $config)) {
            foreach ($config[ '@attributes' ] as $attributeName => $attributeValue) {
                $attribute = $document->createAttribute($attributeName);
                $attribute->value = $attributeValue;
                $node->appendChild($attribute);
            }
            unset($config[ '@attributes' ]);
            if (count($config) > 1) {
                $this->arrayToXml($config, $document, $node);
            } else {
                $values = array_values($config);
                $node->appendChild($document->createCDATASection(reset($values)));
            }
        } else {
            foreach ($config as $key => $value) {
                if (is_array($value)) {
                    if ($this->arrayHelper->isAssociative($value)) {
                        $subNode = $node->appendChild($document->createElement($key));
                        $this->arrayToXml($value, $document, $subNode);
                    } else {
                        foreach ($value as $valueValue) {
                            $subNode = $node->appendChild($document->createElement($key));
                            $this->arrayToXml($valueValue, $document, $subNode);
                        }
                    }
                } else {
                    $valueNode = $document->createElement($key);
                    $valueNode->appendChild($document->createCDATASection($value));
                    $node->appendChild($valueNode);
                }
            }
        }
    }

    /**
     * @param array  $config
     * @param string $rootName
     *
     * @return string
     */
    public function prepareXML(array $config, string $rootName): string
    {
        $document = new \DOMDocument("1.0");

        $document->encoding = 'utf-8';
        $document->preserveWhiteSpace = false;
        $document->formatOutput = true;

        $rootNode = $document->appendChild($document->createElement($rootName));

        $this->arrayToXml($config, $document, $rootNode);

        return $document->saveXML();
    }
}
