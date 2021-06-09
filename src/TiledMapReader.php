<?php

declare(strict_types=1);

namespace RTS;

use DOMDocument;
use DOMXPath;
use RTS\Grid\Grid2D;
use RuntimeException;

final class TiledMapReader
{
    public static function readFile(string $mapFilepath): array
    {
        $map = self::loadMap($mapFilepath);
        $objectAtlas = self::loadObjectAtlas($map, $mapFilepath);

        return [self::loadGrid($map), self::loadTileset($map, $mapFilepath), self::loadUnits($map, $objectAtlas)];
    }

    private static function loadMap(string $path): DOMDocument
    {
        $xml = new DOMDocument();
        if (!$xml->load($path)) {
            throw new RuntimeException("Could not load map file '{$path}'.");
        }

        return $xml;
    }

    private static function loadObjectAtlas(DOMDocument $map, string $mapFilepath): array
    {
        $basedir = realpath(dirname($mapFilepath));
        $atlas = [];

        $xpath = new DOMXPath($map);
        $tilesets = $xpath->query('//map/tileset');
        /** @var \DOMNode $tileset */
        foreach ($tilesets as $tileset) {
            $source = $tileset->attributes->getNamedItem('source');
            if ($source !== null) {
                $tilesetDOM = new DOMDocument();
                $tilesetDOM->load("{$basedir}/{$source->nodeValue}");

                $xpath = new DOMXPath($tilesetDOM);
                $tileset = $tilesetDOM->firstChild;
            }

            $tiles = $xpath->query('./tile', $tileset);
            /** @var \DOMNode $tile */
            foreach ($tiles as $tile) {
                $id = (int) $tile->attributes->getNamedItem('id')->nodeValue;
                $typeAttribute = $tile->attributes->getNamedItem('type');
                $atlas[$id] = [
                    'properties' => [],
                    'type' => $typeAttribute ? $typeAttribute->nodeValue : null,
                    'collision' => null,
                ];

                $properties = $xpath->query('./properties/property', $tile);
                /** @var \DOMNode $property */
                foreach ($properties as $property) {
                    $name = $property->attributes->getNamedItem('name')->nodeValue;
                    $value = $property->attributes->getNamedItem('value')->nodeValue;
                    $atlas[$id]['properties'][$name] = $value;
                }

                $objects = $xpath->query('./objectgroup/object', $tile);
                /** @var \DOMNode $object */
                foreach ($objects as $object) {
                    $type = $object->attributes->getNamedItem('type');

                    if ($type === null) {
                        continue;
                    }

                    if ($type->nodeValue === 'collision') {
                        $atlas[$id]['collision'] = [
                            $object->attributes->getNamedItem('x')->nodeValue,
                            $object->attributes->getNamedItem('y')->nodeValue,
                            $object->attributes->getNamedItem('width')->nodeValue,
                            $object->attributes->getNamedItem('height')->nodeValue,
                        ];
                    }
                }
            }
        }

        return $atlas;
    }

    private static function loadGrid(DOMDocument $map): Grid2D
    {
        $xpath = new DOMXPath($map);
        $data = $xpath->query('//map/layer/data')->item(0);
        $mapDataEncoding = trim($data->attributes->getNamedItem('encoding')->nodeValue);
        switch ($mapDataEncoding) {
            case 'csv':
                $mapDataTxt = trim($data->nodeValue);
                $rows = array_map(
                    fn(string $line) => array_map('intval', array_filter(explode(',', $line))),
                    explode(PHP_EOL, $mapDataTxt)
                );

                $grid = new Grid2D(count($rows[0]), count($rows));
                $c = 0;
                foreach ($rows as $row => $columns) {
                    foreach ($columns as $column => $gid) {
                        $grid[$c++]->data['gid'] = $gid;
                    }
                }

                return $grid;
        }

        throw new RuntimeException("Could not parse map data with format '{$mapDataEncoding}'.");
    }

    private static function loadTileset(DOMDocument $map, string $filepath): Spritesheet
    {
        $basedir = realpath(dirname($filepath));
        $xpath = new DOMXPath($map);
        $tilesets = $xpath->query('//map/tileset');
        /** @var \DOMNode $tileset */
        foreach ($tilesets as $tileset) {
            $source = $tileset->attributes->getNamedItem('source');
            if ($source !== null) {
                $tilesetDOM = new DOMDocument();
                $tilesetDOM->load("{$basedir}/{$source->nodeValue}");

                $xpath = new DOMXPath($tilesetDOM);
                $tileset = $tilesetDOM->firstChild;
            }

            $tilesetAttributes = $tileset->attributes;
            $tileWidth = (int) $tilesetAttributes->getNamedItem('tilewidth')->nodeValue;
            $tileHeight = (int) $tilesetAttributes->getNamedItem('tileheight')->nodeValue;
            $margin = (int) $tilesetAttributes->getNamedItem('margin')->nodeValue;
            $spacing = (int) $tilesetAttributes->getNamedItem('spacing')->nodeValue;

            $imageAttributes = $xpath->query('//tileset/image', $tileset)->item(0)->attributes;
            $tilesetImageSource = realpath(
                dirname($filepath) . '/' . trim($imageAttributes->getNamedItem('source')->nodeValue)
            );

            $texture = GameState::$raylib->loadTexture($tilesetImageSource);
            return new Spritesheet(
                $texture,
                $margin,
                $spacing,
                $tileWidth,
                $tileHeight,
            );
        }
    }

    private static function loadUnits(DOMDocument $map, array $objectAtlas): iterable
    {
        $xpath = new DOMXPath($map);
        $domObjects = $xpath->query('//map/objectgroup/object');

        /** @var \DOMNode $domObject */
        foreach ($domObjects as $domObject) {
            $domObjectAttributes = $domObject->attributes;

            $object = $objectAtlas[(int) $domObjectAttributes->getNamedItem('gid')->nodeValue] ?? [];

            $domProperties = $xpath->query('./properties/property', $domObject);
            foreach ($domProperties as $domProperty) {
                $name = $domProperty->attributes->getNamedItem('name')->nodeValue;
                $value = $domProperty->attributes->getNamedItem('value')->nodeValue;
                $object['properties'][$name] = $value;
            }

            yield [
                ...$object,
                'id' => (int) $domObjectAttributes->getNamedItem('id')->nodeValue,
                'gid' => (int) $domObjectAttributes->getNamedItem('gid')->nodeValue,
                'x' => (int) $domObjectAttributes->getNamedItem('x')->nodeValue,
                'y' => (int) $domObjectAttributes->getNamedItem('y')->nodeValue,
            ];
        }
    }
}
