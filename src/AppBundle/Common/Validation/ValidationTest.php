<?php
namespace AppBundle\Common\Validation;

use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Constraints as Assert;

use PHPUnit_Framework_TestCase;

class ValidationTest extends PHPUnit_Framework_TestCase
{
    public function test1()
    {
        $validator = Validation::createValidator();

        $emailAssert = new Assert\Email;

        $errors = $validator->validate('ahundiak@yahoo.org',$emailAssert);
        $this->assertCount(0,$errors);

        $errors = $validator->validate('ahundiak_yahoo.org',$emailAssert);
        $this->assertCount(1,$errors);
        $this->assertEquals('This value is not a valid email address.',$errors[0]->getMessage());
    }
}
