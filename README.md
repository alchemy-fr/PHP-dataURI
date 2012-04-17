PHP DataURI
============

[![Build Status](https://secure.travis-ci.org/alchemy-fr/PHP-dataURI.png?branch=master)](http://travis-ci.org/alchemy-fr/PHP-dataURI)

##What is it ?

PHP DataURI is  a library which handles URI scheme and provide a way to include
data in-line in web pages as if they were external ressources.

This feature is mainly used with HTML5 file API.

This is supposed 100% RFC 2397 http://tools.ietf.org/html/rfc2397 compliant and ready for production.
 
If you ever find a bug, please fill a ticket or send a pull request.

##License 

MIT http://opensource.org/licenses/MIT


##How to use it ?


Parser
-------

```php
<?php
$dataString = "data:text/plain;charset=utf-8,%23%24%25";
$dataObject = DataURI\Parser::parse($dataString);

echo $dataObject->getMimeType(); //print text/plain
echo $dataObject->getData(); //print #$%
echo $dataObject->getParameters(); // return an array of parameters array('charset' => 'utf-8')

```


Dumper
------

```php
<?php
$dataObject = new DataURI\Data("#$%");
$dataObject->addParameters('charset' => 'utf-8');
echo DaraURI\Dumper::dump(dataObject); //print data:text/plain;charset=utf-8,%23%24%25

```

Data Object From File
---------------------

```php
<?php
$dataObject = DataURI\Data::buildFromFile("/path/to/my/image.png", true);
//second argument is for encoding binaryData in base64
echo DaraURI\Dumper::dump(dataObject); 
//print data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAA...+S/EAAAAASUVORK5CYII=

```

