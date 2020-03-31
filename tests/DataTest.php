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

use DataURI\Data;
use DataURI\Exception\TooLongDataException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

/**
 *
 * @author      Nicolas Le Goff
 * @author      Phraseanet team
 * @license     http://opensource.org/licenses/MIT MIT
 */
class DataTest extends TestCase
{
    /**
     * @var string
     */
    private const LOCALHOST = 'localhost:8000';

    /** @var Process */
    private static $process;

    public static function setUpBeforeClass()
    {
        self::$process = new Process(sprintf('exec php -S %s -t %s', static::LOCALHOST, __DIR__));
        self::$process->start();

        usleep(100000); //wait for server to get going
    }

    public static function tearDownAfterClass()
    {
        self::$process->stop();
    }

    public function testTooLongException()
    {
        $i = 0;
        $string = '';
        while ($i < Data::ATTS_TAG_LIMIT + 1) {
            $string .= 'x';
            $i ++;
        }

        try {
            $dataURI = new Data($string, null, array(), true);
            $this->fail('An exception should have beeen raised');
        } catch (TooLongDataException $e) {
            $this->assertEquals(Data::ATTS_TAG_LIMIT + 1, $e->getLength());
        }

        $dataURI = new Data($string);

        try {
            $dataURI = new Data($string, null, array(), true, Data::LITLEN);
            $this->fail('An exception should have beeen raised');
        } catch (TooLongDataException $e) {

        }
    }

    public function testGetData()
    {
        $dataString = 'Lorem ipsum dolor sit amet';
        $dataURI = new Data($dataString);
        $this->assertEquals($dataString, $dataURI->getData());
    }

    public function testGetMimeType()
    {
        $dataString = 'Lorem ipsum dolor sit amet';
        $mimeType = 'text/plain';
        $dataURI = new Data($dataString, $mimeType);
        $this->assertEquals($mimeType, $dataURI->getMimeType());
    }

    public function testGetParameters()
    {
        $dataString = 'Lorem ipsum dolor sit amet';
        $mimeType = 'text/plain';
        $parameters = array('charset', 'utf-8');
        $dataURI = new Data($dataString, $mimeType, $parameters);
        $this->assertEquals($parameters, $dataURI->getParameters());
        $this->assertInternalType('array', $dataURI->getParameters());
    }

    public function testIsBinaryData()
    {
        $dataString = 'Lorem ipsum dolor sit amet';
        $dataURI = new Data($dataString);
        $dataURI->setBinaryData(true);
        $this->assertTrue($dataURI->isBinaryData());
    }

    public function testInit()
    {
        $dataString = 'Lorem ipsum dolor sit amet';
        $dataURI = new Data($dataString);
        $parameters = $dataURI->getParameters();
        $this->assertArrayHasKey('charset', $parameters);
        $this->assertEquals('US-ASCII', $parameters['charset']);
        $this->assertEquals('text/plain', $dataURI->getMimeType());
    }

    public function testAddParameters()
    {
        $dataString = 'Lorem ipsum dolor sit amet';
        $dataURI = new Data($dataString);
        $current = count($dataURI->getParameters());
        $dataURI->addParameters('charset', 'iso-8859-7');
        $this->assertEquals($current, count($dataURI->getParameters()));
        $dataURI->addParameters('another-charset', 'iso-8859-7');
        $this->assertGreaterThan($current, count($dataURI->getParameters()));
        $this->assertArrayHasKey('another-charset', $dataURI->getParameters());
    }

    public function testBuildFromFile()
    {
        $file = __DIR__ . '/smile.png';
        $dataURI = Data::buildFromFile($file);
        $this->assertInstanceOf('DataURI\Data', $dataURI);
        $this->assertEquals('image/png', $dataURI->getMimeType());
        $this->assertEquals(file_get_contents($file), $dataURI->getData());
    }

    /**
     * @expectedException DataURI\Exception\FileNotFoundException
     */
    public function testBuildFromUrlShouldThrowFileNotFoundException()
    {
        $this->skipIfLocalWebServerDown();

        $url = sprintf('http://%s/unknown.png', static::LOCALHOST);
        Data::buildFromUrl($url);
    }

    public function testBuildFromUrl()
    {
        $this->skipIfLocalWebServerDown();

        $url = sprintf('http://%s/smile.png', static::LOCALHOST);
        $dataURI = Data::buildFromUrl($url);
        $this->assertInstanceOf('DataURI\Data', $dataURI);
        $this->assertEquals('image/png', $dataURI->getMimeType());
        $this->assertEquals(file_get_contents($url), $dataURI->getData());
    }

    /**
     * @expectedException \DataURI\Exception\FileNotFoundException
     */
    public function testFileNotFound()
    {
        $filename = __DIR__ . '/unknown-file';

        $dataString = 'Lorem ipsum dolor sit amet';
        $dataURI = new Data($dataString);
        $dataURI->write($filename);
    }

    /**
     * @expectedException \DataURI\Exception\FileNotFoundException
     */
    public function testFileNotFoundFromFile()
    {
        $filename = __DIR__ . '/unknown-file';

        Data::buildFromFile($filename);
    }

    public function testWrite()
    {
        $filename = __DIR__ . '/test';
        $this->createEmptyFile($filename);
        $dataString = 'hello world';
        $dataURI = new Data($dataString);
        $dataURI = Data::buildFromFile($dataURI->write($filename));
        $this->assertEquals($dataString, $dataURI->getData());
        unlink($filename);
    }

    private function createEmptyFile($filename)
    {
        if (file_exists($filename)) {
            unlink($filename);
        }

        $handle = fopen($filename, 'x+');
        fwrite($handle, '');
        fclose($handle);
    }

    private function skipIfLocalWebServerDown()
    {
        $url = sprintf('http://%s/', static::LOCALHOST);
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch, CURLOPT_TIMEOUT,10);
        curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if (0 === $httpcode) {
            $this->markTestSkipped('The local web server is (still) down.');
        }
    }
}
