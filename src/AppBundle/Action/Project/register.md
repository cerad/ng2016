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

===================================================================
New user is created
  CreateUser sets random User.personKey
  There will be no entry in ProjectPerson for current tournament
  ProjectUserProvider sets ProjectUser.registered to null
  CreateUser performs redirect
  
  KernelListener determines the ProjectUser.registered is null and redirects to project_person_register
  
  project_person_register
    determines that ProjectPerson entry does not exist for ProjectUser
    create ProjectUser entry with registered = null = ??? Is this necessary
    presents ProjectPersonRegister form
    user submits form
      If submit == 'Do not register'
        Set ProjectPerson.registered = FALSE
        The listener will no longer try to redirect to register page
        This project person will now be ignored
        redirect to home
      If submit == 'Register'
        Set ProjectPerson.registered = TRUE
        ... continue processing
        
Existing user logs in
    ProjectUserProvider will attempt to load role information from ProjectPerson
    If no entry in ProjectPerson for current tournament then ProjectUser.registered is null
    Listener forces redirect to register page
    
    project_person_register
        if ProjectPerson projectKey personKey does not exist
           if PhysicalPerson personKey exists 
               check for ayso information under fed
               also pull phone,gender,dob from person table
           else
               use default ProjectPerson info
               
        if ProjectPerson projectKey personKey exists
            pull registration information from existing ProjectPerson entry
            
        Present information on register form
        User submits form
        if submit == 'No register' then
            set ProjectPerson.registered = false
            maybe update any other submitted information?
            redirect to home as an unregistered user
            listener will no longer redirect to register
            be nice if current user could be updated which I think we can do
            
        if submit == register (or maybe update)
            check ayso id
               if no value then
                   parse out referee and other plan info
               if value did not change
                   do nothing about it
               if value changed from existing value(or no existing value)
                   check if new value exists in PhysicalPerson
                       if email as well as ayso id matches existing PhysicalPerson information then
                           Assume all is well
                           Update PersonKey in User as well as ProjectPerson if necessary (usually new user)
                           Pull any referee role information from PhysicalPerson
                       if the ayso id matches but not the email then
                           maybe set some sort of "needs to verify" flag
                           Have fedKeyVerified field
                           
Use case - Assignor reviews all ProjectPerson records with fedKeyVerified set to false
    If everything is fine the set fedKeyVerified to true
    Part of the fedKey verified process will be verifying the orgKey
    If the user changes the org key then the fedKeyVerified will be set to false
    
    Instead of a fedKeyVerified might just have a ProjectPerson verified which gets updated when any of the
    user supplied information is chenged including roles?
    So no need for ProjectPersonRole verified
    Have to think about it
                           
Use case - User intentionally tries to give false ayso id so they can referee at a higher level
    Any user supplied information must be verified with verified being set by assignor
    User cannot self assign unless their information is verified
    