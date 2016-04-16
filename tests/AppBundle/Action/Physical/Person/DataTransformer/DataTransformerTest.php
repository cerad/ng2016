<?php
namespace Tests\AppBundle\Action\Physical\Person\DataTransformer;

use AppBundle\Action\Physical\Person\DataTransformer\PhoneTransformer;
use AppBundle\Action\Physical\Person\DataTransformer\AYSO\RegionKeyTransformer;
use AppBundle\Action\Physical\Person\DataTransformer\AYSO\VolunteerKeyTransformer;

class DataTransformerTest extends \PHPUnit_Framework_TestCase
{
    public function testRegionKeyTransformer()
    {
        $transformer = new RegionKeyTransformer();

        $this->assertEquals(894,$transformer->transform('894'));
        $this->assertEquals(894,$transformer->transform('AYSOR:0894'));

        $this->assertEquals('AYSOR:0894',$transformer->reverseTransform(894));
        
        $this->assertEquals('AYSOR:0894',$transformer->reverseTransform('r894'));

        $this->assertNull($transformer->reverseTransform('abc'));
    }
    public function testVolunteerKeyTransformer()
    {
        $transformer = new VolunteerKeyTransformer();

        $this->assertEquals('12344321',$transformer->transform('12344321'));
        $this->assertEquals('12344321',$transformer->transform('AYSOV:12344321'));

        $this->assertEquals('AYSOV:12344321',$transformer->reverseTransform('12344321'));
        
        $this->assertEquals('AYSOV:12344321',$transformer->reverseTransform('vol12344321'));

        $this->assertNull($transformer->reverseTransform('abc'));
    }
    public function testPhoneTransformer()
    {
        $transformer = new PhoneTransformer();

        $this->assertEquals('(256) 555-1234',$transformer->transform('2565551234'));
        $this->assertEquals('(256) 555-1234',$transformer->transform('256.555.1234'));

        $this->assertEquals('2565551234',$transformer->reverseTransform('256.555.1234'));
    }
}