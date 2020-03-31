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
use PHPUnit\Framework\TestCase;

/**
 *
 * @author      Nicolas Le Goff
 * @author      Phraseanet team
 * @license     http://opensource.org/licenses/MIT MIT
 */
class ParserTest extends TestCase
{

    public function testParse()
    {
        $b64 = $this->binaryToBase64(__DIR__ . '/smile.png');

        $tests = array(
            "data:image/png;base64," . $b64,
            "data:image/png;paramName=paramValue;base64," . $b64,
            "data:text/plain;charset=utf-8,%23%24%25",
            "data:application/vnd-xxx-query,select_vcount,fcol_from_fieldtable/local",
			"data:image/svg+xml;base64," . $b64,
			"data:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;base64," . $b64,
            "data:audio/mp3;base64," . $b64,
            "data:;base64," . $b64,
        );

        $dataURI = Parser::parse($tests[0]);
        $this->assertEquals('image/png', $dataURI->getMimeType());
        $this->assertTrue($dataURI->isBinaryData());
        $this->assertInternalType('string', $dataURI->getData());
        $this->assertEquals(0, count($dataURI->getParameters()));

        $dataURI = Parser::parse($tests[1]);
        $this->assertEquals('image/png', $dataURI->getMimeType());
        $this->assertTrue($dataURI->isBinaryData());
        $this->assertInternalType('string', $dataURI->getData());
        $this->assertEquals(1, count($dataURI->getParameters()));

        $dataURI = Parser::parse($tests[2]);
        $this->assertEquals('text/plain', $dataURI->getMimeType());
        $this->assertFalse($dataURI->isBinaryData());
        $this->assertEquals('#$%', $dataURI->getData());
        $this->assertEquals(1, count($dataURI->getParameters()));

        $dataURI = Parser::parse($tests[4]);
        $this->assertEquals('image/svg+xml', $dataURI->getMimeType());

        $dataURI = Parser::parse($tests[5]);
        $this->assertEquals('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $dataURI->getMimeType());

        $dataURI = Parser::parse($tests[6]);

        $this->assertEquals('audio/mp3', $dataURI->getMimeType());

        $dataURI = Parser::parse($tests[7]);

        $this->assertEquals('text/plain', $dataURI->getMimeType());
}

    /**
     * @expectedException \DataURI\Exception\InvalidDataException
     */
    public function testInvalidDataException()
    {
        $invalidData = 'data:image/gif;base64,';
        Parser::parse($invalidData);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testInvalidArgumentException()
    {
        $invalidData = 'lorem:image:test,datas';
        Parser::parse($invalidData);
    }

    private function binaryToBase64($file)
    {
        return base64_encode(file_get_contents($file));
    }
}
