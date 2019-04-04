<?php
namespace AppBundle\Action\Physical\Person\PersonDataTransformer;

use AppBundle\Action\Physical\Person\DataTransformer\PhoneTransformer;
use PHPUnit_Framework_TestCase;

class PersonDataTransformerTest extends PHPUnit_Framework_TestCase
{
    public function testPhoneTransformer()
    {
        $transformer = new PhoneTransformer();

        $this->assertEquals('(256) 555-1234',$transformer->transform('2565551234'));
        $this->assertEquals('(256) 555-1234',$transformer->transform('256.555.1234'));

        $this->assertEquals('2565551234',$transformer->reverseTransform('256.555.1234'));
    }
}
