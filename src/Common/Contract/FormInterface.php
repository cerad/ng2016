<?php declare(strict_types=1);

namespace Zayso\Common\Contract;

use Symfony\Component\HttpFoundation\Request;

interface FormInterface
{
    // Only public methods
    public function setData(array $formData) : void;
    public function getData() : array;
    public function isValid() : bool;
    public function getSubmit(): string;

    public function handleRequest(Request $request) : void;

    public function render() : string;

    // Not allowed to specify protected because it is an interface
    // The trait will define the interface for the actual forms

    //function renderFormMessages() : string;
    //function renderFormErrors()   : string;

    // Input filtering
    //function filterScalarString (array $data,string $name) : ?string;
    //function filterScalarInteger(array $data,string $name) : ?int;
    //function filterArray        (array $data,string $name,$integer=false); // null,int,string
}