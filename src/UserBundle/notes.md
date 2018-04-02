So back in 2016 had the notion of pull additional user roles from the current project.

For different tournaments a given use could have different permissions.

But would also like to support multi-tournament applications.

    $this->isGranted('referee',$project)
    
The voter would then check for a registered user and pull the role from the project.

This might take care of using the security system.

Overhead for the extra query?

Also have a project person entity used for other stuff.  

Have _role processing for some routes.  ROLE_REFEREE, ROLE_ASSIGNOR

The implication is that we would have a current project listener.

Or move these checks to the actions themselves.  Bit less magic perhaps?  But more code.

Should each project be a bundle?  How about the same project but repeated annually?
