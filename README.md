AYSO Open Cup 2017
========================

Install
--------------

Use composer install (as oppsoed to update) to install or update.  
This ensures that composer.lock will be used which in turn means that the same software versions will be used.

Changes from ng2016
--------------

Renamed the Action directory to Module.

Using the Action Domain Responder nomenclature. 
https://github.com/pmjones/adr
So Controller renamed to Action and View renamed to Responder.

Thought about moving the various module services files to app/config/services per the docs.
This would avoid the need for the DI extension.
Could probably do the same thing with routing.
But going to keep then under Module for now for consistency.

Also considered making individual bundles but want to avoid the mess that ng2014 turned into.
Might still try it later.
CommonBundle
AppBundle
UserBundle
RegPersonBundle
RegTeamBundle
GameBundle

Traits

For ng2016 had a bewildering set of abstract controller/view/form classes.
Trying to clean it up.

Have an ActionTrait which in turn uses RouteTrait and SecurityTrait.
Added setter injections for rthe required services.
Might want a LoggerTrait as well later.

Reused the RouteTrait and SecurityTrait for the KernelListener.

Trying to move as much project specific stuff as possible into the project yaml file.

The Symfony Standard Edition is configured with the following defaults:

  * An AppBundle you can use to start coding;

  * Twig as the only configured template engine;

  * Doctrine ORM/DBAL;

  * Swiftmailer;

  * Annotations enabled for everything.

It comes pre-configured with the following bundles:

  * **FrameworkBundle** - The core Symfony framework bundle

  * [**SensioFrameworkExtraBundle**][6] - Adds several enhancements, including
    template and routing annotation capability

  * [**DoctrineBundle**][7] - Adds support for the Doctrine ORM

  * [**TwigBundle**][8] - Adds support for the Twig templating engine

  * [**SecurityBundle**][9] - Adds security by integrating Symfony's security
    component

  * [**SwiftmailerBundle**][10] - Adds support for Swiftmailer, a library for
    sending emails

  * [**MonologBundle**][11] - Adds support for Monolog, a logging library

  * **WebProfilerBundle** (in dev/test env) - Adds profiling functionality and
    the web debug toolbar

  * **SensioDistributionBundle** (in dev/test env) - Adds functionality for
    configuring and working with Symfony distributions

  * [**SensioGeneratorBundle**][13] (in dev/test env) - Adds code generation
    capabilities

  * **DebugBundle** (in dev/test env) - Adds Debug and VarDumper component
    integration

All libraries and bundles included in the Symfony Standard Edition are
released under the MIT or BSD license.

Enjoy!

[1]:  https://symfony.com/doc/3.2/setup.html
[6]:  https://symfony.com/doc/current/bundles/SensioFrameworkExtraBundle/index.html
[7]:  https://symfony.com/doc/3.2/doctrine.html
[8]:  https://symfony.com/doc/3.2/templating.html
[9]:  https://symfony.com/doc/3.2/security.html
[10]: https://symfony.com/doc/3.2/email.html
[11]: https://symfony.com/doc/3.2/logging.html
[12]: https://symfony.com/doc/3.2/assetic/asset_management.html
[13]: https://symfony.com/doc/current/bundles/SensioGeneratorBundle/index.html
