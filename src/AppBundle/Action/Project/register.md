When an account is created, user is redirected to project_person_register page.

They have the option of registering for the tournament or deciding not to.

When an unregistered user logs in for the first time they should have the option of registering.

How to tell if the user has declined to register?

ProjectPerson
    projectKey
    personKey
    registered = true/false
    
Or
UserProject
    userId
    projectKey
    registered
    
An entry in UserProject indicates that the user has had a chance to register in the project.

The UserProject table could also be used to view teams.

Create User Person
  At this point we do not know about their eayso id.
  Could prompt for it during the Create User form.
  If given then we could check for existing person record.
  If not then maybe create one?
  
Make all users answer register question before proceeding to the site.
