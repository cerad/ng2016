<?php

namespace Zayso\Project;

/**
 * @property-read string $name
 * @property-read string $email
 * @property-read string $phone
 * @property-read string $subject
 */
class ProjectContact
{
    public $name;
    public $email;
    public $phone;
    public $subject;

    public function __construct(string $name, string $email, string $phone, string $subject = null)
    {
        $this->name    = $name;
        $this->email   = $email;
        $this->phone   = $phone;
        $this->subject = $subject;
    }
}