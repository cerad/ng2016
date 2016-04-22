Symfony Security component needs an object which implements UserInterface.

We implement the AdvancedUserInterface to get support for enabling/disabling accounts.

Implement array access so the object looks like an array for most of the code.

Most of the code outside of the security system just uses an actual array.

Always load the user for security from the user provider.

The serializer interfaces to the session storage.  

Hint: remove var/sessions/env data when changing the serializer.

Side note: why does _csrf/authenticate session hang around?

That only thing that needs to be saved is the id?

Before I had trouble if salt/password was not being stored but seems to be fine now.  
Storing the username just for grins.

Complete user object is reloaded on each request by UserProvider::refreshUser.

No real performance issue and avoids needing to logout when roles change.

Speaking of which.  When a new user registers as a referee the a ROLE_REFEREE is added.
Want the security system to recognise this without requiring a logout/login.

Seems to work okay.  Not sure why I had problems before.  Maybe I had the redirect disabled?


