<?php

/**
 * Copyright (c) 2012 Alchemy-fr
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"),
 * to deal in the Software without restriction, including without limitation
 * the rights to use, copy, modify, merge, publish, distribute, sublicense,
 * and/or sell copies of the Software, and to permit persons to whom the
 * Software is furnished to do so, subject to the following conditions:
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 */

namespace DataURI\Tests;

use DataURI\Parser;
use DataURI\Dumper;
use PHPUnit\Framework\TestCase;

/**
 *
 * @author      Nicolas Le Goff
 * @author      Phraseanet team
 * @license     http://opensource.org/licenses/MIT MIT
 */
class DumperTest extends TestCase
{

    public function dumpDataProvider()
    {
        $b64 = $this->binaryToBase64(__DIR__ . '/smile.png');

        return array(
            array("data:image/png;base64," . $b64),
            array("data:image/png;paramName=paramValue;base64," . $b64),
            array("data:text/plain;charset=utf-8,%23%24%25")
        );
    }

    /**
     * @dataProvider dumpDataProvider
     */
    public function testDump($expectedValue)
    {
        $dataURI = Parser::parse($expectedValue);
        $this->assertEquals($expectedValue, Dumper::dump($dataURI));
    }

    public function testDumpOnRawUrlDecodeString()
    {
        $dataURI = Parser::parse("data:application/vnd-xxx-query,select_vcount,fcol_from_fieldtable/local");
        $this->assertEquals("data:application/vnd-xxx-query,select_vcount,fcol_from_fieldtable/local", rawurldecode(Dumper::dump($dataURI)));
    }

    private function binaryToBase64($file)
    {
        return base64_encode(file_get_contents($file));
    }
}
