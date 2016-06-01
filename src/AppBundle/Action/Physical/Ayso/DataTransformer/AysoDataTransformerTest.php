<?php
namespace AppBundle\Action\Physical\Ayso\AysoDataTransformer;

use AppBundle\Action\Physical\Ayso\DataTransformer\RegionKeyTransformer;
use AppBundle\Action\Physical\Ayso\DataTransformer\RegionToSarTransformer;
use AppBundle\Action\Physical\Ayso\DataTransformer\VolunteerKeyTransformer;
use AppBundle\Action\Physical\Ayso\PhysicalAysoRepository;
use AppBundle\AbstractTestDatabase;

class AysoDataTransformerTest extends AbstractTestDatabase
{
    public function setUp()
    {
        $this->databaseNameKey = 'database_name_ayso';

        parent::setUp();
    }

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

        $this->assertEquals('12344321', $transformer->transform('12344321'));
        $this->assertEquals('12344321', $transformer->transform('AYSOV:12344321'));

        $this->assertEquals('AYSOV:12344321', $transformer->reverseTransform('12344321'));

        $this->assertEquals('AYSOV:12344321', $transformer->reverseTransform('vol12344321'));

        $this->assertNull($transformer->reverseTransform('abc'));
    }
    public function testRegionToSarTransformer()
    {
        // Really should use the test database for this
        $aysoRepository = new PhysicalAysoRepository($this->conn);

        $transformer = new RegionToSarTransformer($aysoRepository);

        $sar = $transformer->transform('AYSOR:0894');
        $this->assertEquals('5/C/0894/AL',$sar);

        $orgKey = $transformer->reverseTransform('5/C/0498');
        $this->assertEquals('AYSOR:0498',$orgKey);
    }
}
