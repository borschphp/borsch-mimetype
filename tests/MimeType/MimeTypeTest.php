<?php
/**
 * @author    Alexandre DEBUSSCHÈRE <alexandre@kosmonaft.dev>
 * @copyright 2021 Kosmonaft
 * @license   Commercial
 */

namespace BorschTest\MimeType;

use Borsch\MimeType\MimeType;
use PHPUnit\Framework\TestCase;

class MimeTypeTest extends TestCase
{

    public function testConstructorWithEmptyType()
    {
        $this->expectException(\InvalidArgumentException::class);
        new MimeType('', 'json');
    }

    public function testConstructorWithEmptySubtype()
    {
        $this->expectException(\InvalidArgumentException::class);
        new MimeType('application', '');
    }

    public function testConstructorWithInvalidType()
    {
        $this->expectException(\InvalidArgumentException::class);
        new MimeType('application[]', 'json');
    }

    public function testConstructorWithInvalidSubtype()
    {
        $this->expectException(\InvalidArgumentException::class);
        new MimeType('application', 'json{}');
    }

    public function testConstructorWithInvalidParameterName()
    {
        $this->expectException(\InvalidArgumentException::class);
        new MimeType('application', 'json', ['' => 'utf8']);
    }

    public function testConstructorWithInvalidParameterValue()
    {
        $this->expectException(\InvalidArgumentException::class);
        new MimeType('application', 'json', ['charset' => '']);
    }

    public function testIsWildcardType()
    {
        $mime_type = new MimeType();
        $this->assertTrue($mime_type->isWildcardType());
    }

    public function testIsNotWildcardType()
    {
        $mime_type = new MimeType('application', 'json', ['charset' => 'utf-8']);
        $this->assertFalse($mime_type->isWildcardType());
    }

    public function testIsIn()
    {
        $mime_type = new MimeType('application', 'json', ['charset' => 'utf-8']);
        $mime_types = [
            new MimeType('application', 'cbor'),
            new MimeType('application', 'xml'),
            new MimeType('text', 'plain'),
            new MimeType('text', 'markdown'),
            new MimeType('application', 'json'),
            new MimeType('image', 'png')
        ];
        $this->assertTrue($mime_type->isIn($mime_types), 'MimeType "application/json" is in the array.');
    }

    public function testIsNotIn()
    {
        $mime_type = new MimeType('application', 'json', ['charset' => 'utf-8']);
        $mime_types = [
            new MimeType('application', 'cbor'),
            new MimeType('application', 'xml'),
            new MimeType('text', 'plain'),
            new MimeType('text', 'markdown'),
            new MimeType('image', 'png')
        ];
        $this->assertFalse($mime_type->isIn($mime_types));
    }

    public function testIsInThrowExceptionIfRecordInArrayIsNotInstanceOfMimeType()
    {
        $mime_type = new MimeType('application', 'json', ['charset' => 'utf-8']);
        $mime_types = [
            new MimeType('application', 'cbor'),
            new MimeType('application', 'xml'),
            ['text', 'plain'],
            new MimeType('text', 'markdown'),
            new MimeType('image', 'png')
        ];
        $this->expectException(\InvalidArgumentException::class);
        $mime_type->isIn($mime_types);
    }

    public function testGetType()
    {
        $mime_type = new MimeType('application', 'json', ['charset' => 'utf-8']);
        $this->assertEquals('application', $mime_type->getType());
    }

    public function testGetCharsetUtf8()
    {
        $mime_type = new MimeType('application', 'json', ['charset' => 'utf-8']);
        $this->assertEquals('utf-8', $mime_type->getCharset());
    }

    public function testGetCharsetNull()
    {
        $mime_type = new MimeType('application', 'json');
        $this->assertEquals(null, $mime_type->getCharset());
    }

    public function testGetParameter()
    {
        $mime_type = new MimeType('application', 'json', ['charset' => 'utf-8']);
        $this->assertEquals('utf-8', $mime_type->getParameter('charset'));
    }

    public function testGetParameterReturnsNullIfNotFound()
    {
        $mime_type = new MimeType('application', 'json');
        $this->assertNull($mime_type->getParameter('charset'));
    }

    public function testGetSubtype()
    {
        $mime_type = new MimeType('application', 'json', ['charset' => 'utf-8']);
        $this->assertEquals('json', $mime_type->getSubtype());
    }

    public function testCreateFromEmptyStringThrowException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $instance = MimeType::createFromString('');
    }

    public function testCreateFromStringWithoutParameters()
    {
        $mime_type = 'application/json';
        $instance = MimeType::createFromString($mime_type);
        $this->assertInstanceOf(MimeType::class, $instance);
        $this->assertEquals('application', $instance->getType());
        $this->assertEquals('json', $instance->getSubtype());
    }

    public function testCreateFromStringWithParameters()
    {
        $mime_type = 'application/atom+xml;charset=utf-8;boundary=3d6b6a416f9b5;name=some_file';
        $instance = MimeType::createFromString($mime_type);
        $this->assertInstanceOf(MimeType::class, $instance);
        $this->assertEquals('application', $instance->getType());
        $this->assertEquals('atom+xml', $instance->getSubtype());
        $this->assertEquals('xml', $instance->getSubtypeSuffix());
        $this->assertCount(3, $instance->getParameters());
        $this->assertEquals('utf-8', $instance->getParameter('charset'));
        $this->assertEquals('3d6b6a416f9b5', $instance->getParameter('boundary'));
        $this->assertEquals('some_file', $instance->getParameter('name'));
    }

    public function testCreateFromStringWithMissingTypes()
    {
        $mime_type = 'application;charset=utf-8';

        $this->expectException(\InvalidArgumentException::class);
        MimeType::createFromString($mime_type);
    }

    public function testCreateFromStringWithIllegalWildcard()
    {
        $mime_type = '*/json;charset=utf-8';

        $this->expectException(\InvalidArgumentException::class);
        MimeType::createFromString($mime_type);
    }

    public function testTextIsCompatibleWithTextPlain()
    {
        $mime_type = new MimeType('text');
        $this->assertTrue($mime_type->isCompatibleWith(new MimeType('text', 'plain')));
    }

    public function testWildcardIsCompatibleWithTextPlain()
    {
        $mime_type = new MimeType();
        $this->assertTrue($mime_type->isCompatibleWith(new MimeType('text', 'plain')));
    }

    public function testWildcardIsCompatibleWithSubtypeSuffix()
    {
        $mime_type = new MimeType('application', '*+problem');
        $this->assertTrue($mime_type->isCompatibleWith(new MimeType('application', 'json+problem')));
    }

    public function testWildcardIsCompatibleWithSubtypeSuffixReverse()
    {
        $mime_type = new MimeType('application', 'json+problem');
        $this->assertTrue($mime_type->isCompatibleWith(new MimeType('application', '*+problem')));
    }

    public function testApplicationIsNotCompatibleWithTextPlain()
    {
        $mime_type = new MimeType('application', 'json');
        $this->assertFalse($mime_type->isCompatibleWith(new MimeType('text', 'plain')));
    }

    public function testEquals()
    {
        $mime_type = new MimeType('application', 'json');
        $this->assertTrue($mime_type->equals(new MimeType('application', 'json')));
    }

    public function testEqualsWithSameParameter()
    {
        $mime_type = new MimeType('application', 'json', ['charset' => 'utf-8']);
        $this->assertTrue($mime_type->equals(new MimeType('application', 'json', ['charset' => 'utf-8'])));
    }

    public function testEqualsWithDifferentParameter()
    {
        $mime_type = new MimeType('application', 'json', ['charset' => 'utf-8']);
        $this->assertFalse($mime_type->equals(new MimeType('application', 'json', ['charset' => 'utf-16'])));
    }

    public function testEqualsWithMissingParameter()
    {
        $mime_type = new MimeType('application', 'json', ['charset' => 'utf-8']);
        $this->assertFalse($mime_type->equals(new MimeType('application', 'json')));
    }

    public function testGetSubtypeSuffixNull()
    {
        $mime_type = new MimeType('application', 'json');
        $this->assertNull($mime_type->getSubtypeSuffix());
    }

    public function testGetSubtypeSuffixXml()
    {
        $mime_type = new MimeType('application', 'atom+xml');
        $this->assertEquals('xml', $mime_type->getSubtypeSuffix());
    }

    public function testEqualsTypeAndSubtypeFalse()
    {
        $mime_type1 = new MimeType('application', 'json');
        $mime_type2 = new MimeType('application', 'x-ms-application');
        $this->assertFalse($mime_type1->equalsTypeAndSubtype($mime_type2));

        $mime_type1 = new MimeType('application', 'json');
        $mime_type2 = new MimeType('text', 'plain');
        $this->assertFalse($mime_type1->equalsTypeAndSubtype($mime_type2));

        $mime_type1 = new MimeType('application', 'json');
        $mime_type2 = new MimeType('application', 'json+random');
        $this->assertFalse($mime_type1->equalsTypeAndSubtype($mime_type2));

        $mime_type1 = new MimeType('application', 'json');
        $mime_type2 = new MimeType('application', 'json');
        $this->assertTrue($mime_type1->equalsTypeAndSubtype($mime_type2));
    }

    public function testEqualsTypeAndSubtypeTrue()
    {
        $mime_type1 = new MimeType('application', 'json');
        $mime_type2 = new MimeType('application', 'json');
        $this->assertTrue($mime_type1->equalsTypeAndSubtype($mime_type2));

        $mime_type1 = new MimeType('application', 'json');
        $mime_type2 = new MimeType('application', 'json', ['charset', 'utf-8']);
        $this->assertTrue($mime_type1->equalsTypeAndSubtype($mime_type2));

        $mime_type1 = new MimeType('application', 'atom+xml');
        $mime_type2 = new MimeType('application', 'atom+xml');
        $this->assertTrue($mime_type1->equalsTypeAndSubtype($mime_type2));

        $mime_type1 = new MimeType('application', 'json');
        $mime_type2 = new MimeType('application', 'json+random');
        $this->assertFalse($mime_type1->equalsTypeAndSubtype($mime_type2));
    }

    public function testIsConcrete()
    {
        $mime_type = new MimeType('application', 'json');
        $this->assertTrue($mime_type->isConcrete());

        $mime_type = new MimeType('*', 'json');
        $this->assertFalse($mime_type->isConcrete());

        $mime_type = new MimeType('*', '*');
        $this->assertFalse($mime_type->isConcrete());
    }

    public function testIncludes()
    {
        $mime_type_1 = new MimeType('text', '*');
        $mime_type_2 = new MimeType('text', 'plain');
        $this->assertTrue($mime_type_1->includes($mime_type_2));


        $mime_type_2 = new MimeType('text', 'html');
        $this->assertTrue($mime_type_1->includes($mime_type_2));

        $mime_type_1 = new MimeType('*', '*');
        $mime_type_2 = new MimeType('text', 'plain');
        $this->assertTrue($mime_type_1->includes($mime_type_2));

        $mime_type_1 = new MimeType('application', '*+xml');
        $mime_type_2 = new MimeType('application', 'soap+xml');
        $this->assertTrue($mime_type_1->includes($mime_type_2));
    }

    public function testDoesNotInclude()
    {
        $mime_type_1 = new MimeType('text', '*');
        $mime_type_2 = new MimeType('application', 'json');
        $this->assertFalse($mime_type_1->includes($mime_type_2));

        $mime_type_1 = new MimeType('application', '*+xml');
        $mime_type_2 = new MimeType('application', 'soap+random');
        $this->assertFalse($mime_type_1->includes($mime_type_2));
    }

    public function testGetParameters()
    {
        $mime_type = new MimeType('application', 'json', [
            'charset' => 'utf-8',
            'boundary' => '3d6b6a416f9b5',
            'name' => 'some_file',
        ]);
        $this->assertIsArray($mime_type->getParameters());
        $this->assertCount(3, $mime_type->getParameters());
        $this->assertArrayHasKey('charset', $mime_type->getParameters());
        $this->assertArrayHasKey('boundary', $mime_type->getParameters());
        $this->assertArrayHasKey('name', $mime_type->getParameters());
        $this->assertEquals('utf-8', $mime_type->getParameters()['charset']);
        $this->assertEquals('3d6b6a416f9b5', $mime_type->getParameters()['boundary']);
        $this->assertEquals('some_file', $mime_type->getParameters()['name']);
    }

    public function testGetParametersEmptyArrayIfNone()
    {
        $mime_type = new MimeType('application', 'json');
        $this->assertIsArray($mime_type->getParameters());
        $this->assertCount(0, $mime_type->getParameters());
    }

    public function test__toString()
    {
        $mime_type = new MimeType('application', 'json');
        $this->assertEquals('application/json', (string)$mime_type);

        $mime_type = new MimeType('application', 'json', [
            'charset' => 'utf-8',
            'boundary' => '3d6b6a416f9b5',
            'name' => 'some_file',
        ]);
        $this->assertEquals(
            'application/json;charset=utf-8;boundary=3d6b6a416f9b5;name=some_file',
            (string)$mime_type
        );
    }

    public function testIsWildcardSubtype()
    {
        $mime_type = new MimeType('application', 'soap+xml');
        $this->assertFalse($mime_type->isWildcardSubtype());

        $mime_type = new MimeType('application', '*+xml');
        $this->assertTrue($mime_type->isWildcardSubtype());

        $mime_type = new MimeType('application', '*');
        $this->assertTrue($mime_type->isWildcardSubtype());

    }

    public function testIsNotWildcardSubtype()
    {
        $mime_type = new MimeType('application', 'soap+xml');
        $this->assertFalse($mime_type->isWildcardSubtype());

        $mime_type = new MimeType('application', '*+xml');
        $this->assertTrue($mime_type->isWildcardSubtype());

        $mime_type = new MimeType('application', '*');
        $this->assertTrue($mime_type->isWildcardSubtype());
    }
}
