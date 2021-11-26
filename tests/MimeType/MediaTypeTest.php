<?php
/**
 * @author    Alexandre DEBUSSCHÈRE <alexandre@kosmonaft.dev>
 * @copyright 2021 Kosmonaft
 * @license   Commercial
 */

namespace BorschTest\MimeType;

use Borsch\MimeType\MediaType;
use PHPUnit\Framework\TestCase;

class MediaTypeTest extends TestCase
{

    public function testGetDefinedQualityValue()
    {
        $media_type = new MediaType('image', 'jpeg', ['q' => 0.8]);
        $this->assertEquals(0.8, $media_type->getQualityValue());
    }

    public function testGetDefaultQualityValue()
    {
        $media_type = new MediaType('image', 'jpeg');
        $this->assertIsFloat($media_type->getQualityValue());
        $this->assertEquals(1, $media_type->getQualityValue());
    }

    public function testGetDefaultQualityValueUnquoted()
    {
        $media_type = new MediaType('image', 'jpeg', ['q' => '"0.8"']);
        $this->assertIsFloat($media_type->getQualityValue());
        $this->assertEquals(0.8, $media_type->getQualityValue());
    }

    public function testCopyQualityValueWithoutQualityValueParameter()
    {
        $media_type_1 = new MediaType('image', 'jpeg', ['q' => 0.7]);
        $media_type_2 = new MediaType('image', 'jpeg');

        $media_type_1 = $media_type_1->copyQualityValue($media_type_2);
        $this->assertInstanceOf(MediaType::class, $media_type_1);
        $this->assertEquals(0.7, $media_type_1->getQualityValue());
    }

    public function testCopyQualityValueWithQualityValueParameter()
    {
        $media_type_1 = new MediaType('image', 'jpeg', ['q' => 0.7]);
        $media_type_2 = new MediaType('image', 'jpeg', ['q' => '"0.8"']);

        $media_type_1 = $media_type_1->copyQualityValue($media_type_2);
        $this->assertInstanceOf(MediaType::class, $media_type_1);
        $this->assertEquals(0.8, $media_type_1->getQualityValue());
    }

    public function testRemoveQualityValue()
    {
        $media_type = new MediaType('image', 'jpeg', ['q' => 0.8]);
        $this->assertArrayHasKey('q', $media_type->getParameters());
        $this->assertEquals(0.8, $media_type->getQualityValue());
        $this->assertEquals(0.8, $media_type->getParameter('q'));

        $this->assertArrayNotHasKey('q', $media_type->removeQualityValue()->getParameters());
    }

    public function testRemoveQualityValueWithoutQualityValueReturnCurrentInstance()
    {
        $media_type = new MediaType('image', 'jpeg');
        $this->assertEquals($media_type, $media_type->removeQualityValue());
    }

    public function testValidateParametersWithQualityHigherThanOneThrowsException()
    {
        $this->expectException(\InvalidArgumentException::class);
        new MediaType('image', 'jpeg', ['q' => 1.8]);
    }

    public function testValidateParametersWithQualityLowerThanZeroThrowsException()
    {
        $this->expectException(\InvalidArgumentException::class);
        new MediaType('image', 'jpeg', ['q' => -0.42]);
    }

    public function testValidateParametersWithQuotedQuality()
    {
        $media_type = new MediaType('image', 'jpeg', ['q' => '"0.8"']);
        $this->assertEquals(0.8,  $media_type->getQualityValue());
    }

    public function testValidateParametersWithUnquoteQuality()
    {
        $media_type = new MediaType('image', 'jpeg', ['q' => 0.8]);
        $this->assertEquals(0.8,  $media_type->getQualityValue());
    }
}
