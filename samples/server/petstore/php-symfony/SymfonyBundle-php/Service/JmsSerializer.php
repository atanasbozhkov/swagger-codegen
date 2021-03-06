<?php

namespace Swagger\Server\Service;

use JMS\Serializer\SerializerBuilder;
use JMS\Serializer\Naming\CamelCaseNamingStrategy;
use JMS\Serializer\Naming\SerializedNameAnnotationStrategy;
use Swagger\Server\Service\StrictJsonDeserializationVisitor;
use JMS\Serializer\XmlDeserializationVisitor;

class JmsSerializer implements SerializerInterface
{
    protected $serializer;

    public function __construct()
    {
        $naming_strategy = new SerializedNameAnnotationStrategy(new CamelCaseNamingStrategy());
        $this->serializer = SerializerBuilder::create()
            ->setDeserializationVisitor('json', new StrictJsonDeserializationVisitor($naming_strategy))
            ->setDeserializationVisitor('xml', new XmlDeserializationVisitor($naming_strategy))
            ->build();
    }

    public function serialize($data, $format)
    {
        return SerializerBuilder::create()->build()->serialize($data, $this->convertFormat($format));
    }

    public function deserialize($data, $type, $format)
    {
        if ($format == 'string') {
            return $this->deserializeString($data, $type);           
        }

        // If we end up here, let JMS serializer handle the deserialization
        return $this->serializer->deserialize($data, $type, $this->convertFormat($format));
    }

    private function convertFormat($format)
    {
        switch ($format) {
            case 'application/json':
                return 'json';
            case 'application/xml':
                return 'xml';
        }

        return null;
    }

    private function deserializeString($data, $type)
    {
        switch ($type) {
            case 'int':
            case 'integer':
                if (is_int($data)) {
                    return $data;
                }

                if (is_numeric($data)) {
                    return $data + 0;
                }

                break;
            case 'string':
                break;
            case 'boolean':
            case 'bool':
                if (strtolower($data) === 'true') {
                    return true;
                }

                if (strtolower($data) === 'false') {
                    return false;
                }

                break;
            case 'array<csv>':
                return explode(',', $data);
            case 'array<ssv>':
                return explode(' ', $data);
            case 'array<tsv>':
                return explode("\t", $data);
            case 'array<pipes>':
                return explode('|', $data);
        }

        // If we end up here, just return data
        return $data;
    }
}
