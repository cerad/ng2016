13 July 2017

To approve assignments

UPDATE gameOfficials SET assignState = 'Approved' WHERE projectId = 'AYSONationalOpenCup2017' AND assignState = 'Requested';

UPDATE gameOfficials SET assignRole = 'ROLE_REFEREE' WHERE projectId = 'AYSONationalOpenCup2017' AND assignRole = 'ROLE_ASSIGNOR';

Need to be able to create registered and schedule teams from either spreadsheet or user interface.
Currently need to write a script to do this.  Bit awkward for changes.

Need to remove keys from the primary id as it makes changing things like game numbers very difficult.

On the other hand, ussing the schedule team key for game schedules seems to work okay.

Club divisions were specified by using year of birth "Boys 04-05" instead of age.  Somewhat strange.

Want to be able to switch to a psuedo referee and assignor user.

Add sort by to admin game listing for when game numbers are wonky

U11BClub has two brackets but all cross pool play - standings?

If a reg team has not been assigned, consider adding pool team view to exported game schedule

Adult men vs youth boy or male vs female
coed

Test remove by assignor without clearing the referee name


