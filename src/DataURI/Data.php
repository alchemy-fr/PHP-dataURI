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

namespace DataURI;

use DataURI\Exception\FileNotFoundException;
use DataURI\Exception\TooLongDataException;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException as SymfonyFileException;

/**
 * DataURI\Data object is a representation of an url which embed (small)
 * media type data directly inline.
 *
 * It owns three main properties :
 * the data, the type of the media and some optional parameters
 *
 * @author      Nicolas Le Goff
 * @author      Phraseanet team
 * @license     http://opensource.org/licenses/MIT MIT
 */
class Data
{
    /**
     * The LITLEN (1024) limits the number of characters which can appear in
     * a single attribute value literal
     */
    const LITLEN = 0;

    /**
     * The ATTSPLEN (2100) limits the sum of all
     * lengths of all attribute value specifications which appear in a tag
     */
    const ATTSPLEN = 1;

    /**
     * The TAGLEN (2100) limits the overall length of a tag
     */
    const TAGLEN = 2;

    /**
     * ATTS_TAG_LIMIT is the length limit allowed for TAGLEN & ATTSPLEN DataURi
     */
    const ATTS_TAG_LIMIT = 2100;

    /**
     * LIT_LIMIT is the length limit allowed for LITLEN DataURi
     */
    const LIT_LIMIT = 1024;

    /**
     * Base64 encode prefix
     */
    const BASE_64 = 'base64';

    /**
     * File data
     * @var string
     */
    protected $data;

    /**
     * File mime type
     * @var string
     */
    protected $mimeType;

    /**
     * Parameters provided in DataURI
     * @var Array
     */
    protected $parameters;

    /**
     * Tell whether data is binary datas
     * @var boolean
     */
    protected $isBinaryData = false;

    /**
     * A DataURI Object which by default has a 'text/plain'
     * media type and a 'charset=US-ASCII' as optional parameter
     *
     * @param string    $data       Data to include as "immediate" data
     * @param string    $mimeType   Mime type of media
     * @param array     $parameters Array of optional parameters
     * @param boolean   $strict     Check length of data
     * @param int       $lengthMode Define Length of data
     */
    public function __construct(
        $data,
        $mimeType = null,
        array $parameters = array(),
        $strict = false,
        $lengthMode = self::TAGLEN
    ) {
        $this->data = $data;
        $this->mimeType = $mimeType;
        $this->parameters = $parameters;

        $this->init($lengthMode, $strict);

        $this->isBinaryData = strpos($this->mimeType, 'text/') !== 0;
    }

    /**
     * File contents
     * @return string
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Media type
     * @return string
     */
    public function getMimeType()
    {
        return $this->mimeType;
    }

    /**
     * File parameters
     * @return mixed[]
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * Data is binary data
     * @return boolean
     */
    public function isBinaryData()
    {
        return $this->isBinaryData;
    }

    /**
     * Set if Data is binary data
     *
     * @param boolean $boolean
     * @return $this
     */
    public function setBinaryData($boolean)
    {
        $this->isBinaryData = (boolean) $boolean;

        return $this;
    }

    /**
     * Add a custom parameters to the DataURi
     *
     * @param string $paramName
     * @param string $paramValue
     * @return $this
     */
    public function addParameters($paramName, $paramValue)
    {
        $this->parameters[$paramName] = $paramValue;

        return $this;
    }

    /**
     * Write datas to the specified file
     *
     * @param string    $filePath   File to be written
     * @param Boolean   $overwrite   Override existing file
     *
     * @return \Symfony\Component\HttpFoundation\File\File
     *
     * @throws FileNotFoundException
     */
    public function write($filePath, $overwrite = false)
    {
        if ( ! file_exists($filePath)) {
            throw new FileNotFoundException(sprintf('%s file does not exist', $filePath));
        }

        file_put_contents($filePath, $this->data, $overwrite ? 0 : FILE_APPEND);

        return new File($filePath);
    }

    /**
     * Get a new instance of DataUri\Data from a file
     *
     * @param string $file Path to the located file
     * @param boolean $strict Use strict mode
     * @param int $lengthMode The length mode
     * @return Data
     * @throws FileNotFoundException
     */
    public static function buildFromFile($file, $strict = false, $lengthMode = Data::TAGLEN)
    {
        if ( ! $file instanceof File) {
            try {
                $file = new File($file);
            } catch (SymfonyFileException $e){
                throw new FileNotFoundException(sprintf('%s file does not exist', $file));
            }
        }

        $data = file_get_contents($file->getPathname());

        $dataURI = new static($data, $file->getMimeType(), array(), $strict, $lengthMode);

        return $dataURI;
    }

    /**
     * Get a new instance of DataUri\Data from a remote file
     *
     * @param string $url Path to the remote file
     * @param boolean $strict Use strict mode
     * @param int $lengthMode The length mode
     * @return Data
     * @throws FileNotFoundException
     */
    public static function buildFromUrl($url, $strict = false, $lengthMode = Data::TAGLEN)
    {
        if (! extension_loaded('curl')) {
            throw new \RuntimeException('This method requires the CURL extension.');
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $data = curl_exec($ch);

        if (curl_getinfo($ch, CURLINFO_HTTP_CODE) != 200) {
            curl_close($ch);
            throw new FileNotFoundException(sprintf('%s file does not exist or the remote server does not respond', $url));
        }

        $mimeType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);

        $dataURI = new static($data, $mimeType, array(), $strict, $lengthMode);

        return $dataURI;
    }

    /**
     * Constructor initialization

     *
     * @param int       $lengthMode     Max allowed data length
     * @param boolean   $strict         Check data length
     * @throws TooLongDataException
     * @return void
     */
    private function init($lengthMode, $strict)
    {
        if ($strict && $lengthMode === self::LITLEN && strlen($this->data) > self::LIT_LIMIT) {
            throw new TooLongDataException('Too long data', strlen($this->data));
        } elseif ($strict && strlen($this->data) > self::ATTS_TAG_LIMIT) {
            throw new TooLongDataException('Too long data', strlen($this->data));
        }

        if (null === $this->mimeType) {
            $this->mimeType = 'text/plain';
            $this->addParameters('charset', 'US-ASCII');
        }

        return;
    }
}
