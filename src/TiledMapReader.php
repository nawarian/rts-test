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

        return [self::loadGrid($map), self::loadTileset($map, $mapFilepath), self::loadUnits($map)];
    }

    private static function loadMap(string $path): DOMDocument
    {
        $xml = new DOMDocument();
        if (!$xml->load($path)) {
            throw new RuntimeException("Could not load map file '{$path}'.");
        }

        return $xml;
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
        $xpath = new DOMXPath($map);
        $tilesetNode = $xpath->query('//map/tileset')->item(0);

        $tilesetSourceDefinitionsPath = realpath(
            dirname($filepath) . '/' . trim($tilesetNode->attributes->getNamedItem('source')->nodeValue)
        );

        $tilesetXml = new DOMDocument();
        if (!$tilesetXml->load($tilesetSourceDefinitionsPath)) {
            $tilesetFilename = basename($tilesetSourceDefinitionsPath);
            $fileBaseName = basename($filepath);
            throw new RuntimeException(
                "Could not load tileset file '{$tilesetFilename}'. Referenced from {$fileBaseName}."
            );
        }
        $tilesetXpath = new DOMXPath($tilesetXml);

        $tilesetAttributes = $tilesetXml->firstChild->attributes;
        $tileWidth = (int) $tilesetAttributes->getNamedItem('tilewidth')->nodeValue;
        $tileHeight = (int) $tilesetAttributes->getNamedItem('tileheight')->nodeValue;
        $margin = (int) $tilesetAttributes->getNamedItem('margin')->nodeValue;
        $spacing = (int) $tilesetAttributes->getNamedItem('spacing')->nodeValue;

        $imageAttributes = $tilesetXpath->query('//tileset/image')->item(0)->attributes;
        $tilesetImageSource = realpath(
            dirname($filepath) . '/' . trim($imageAttributes->getNamedItem('source')->nodeValue)
        );

        $texture = $texture = GameState::$raylib->loadTexture($tilesetImageSource);
        return new Spritesheet(
            $texture,
            $margin,
            $spacing,
            $tileWidth,
            $tileHeight,
        );
    }

    private static function loadUnits(DOMDocument $map): iterable
    {
        $xpath = new DOMXPath($map);
        $objects = $xpath->query('//map/objectgroup/object');

        for ($i = 0; $i < $objects->count(); ++$i) {
            $obj = $objects->item($i)->attributes;
            yield [
                'id' => (int) $obj->getNamedItem('id')->nodeValue,
                'gid' => (int) $obj->getNamedItem('gid')->nodeValue,
                'name' => trim($obj->getNamedItem('name')->nodeValue),
                'type' => trim($obj->getNamedItem('type')->nodeValue),
                'x' => (int) $obj->getNamedItem('x')->nodeValue,
                'y' => (int) $obj->getNamedItem('y')->nodeValue,
                'width' => (int) $obj->getNamedItem('width')->nodeValue,
                'height' => (int) $obj->getNamedItem('height')->nodeValue,
            ];
        }
    }
}
