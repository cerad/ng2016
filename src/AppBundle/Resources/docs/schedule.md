For ng2014 we had a teams table initially loaded with the core teams.
This table ended up not being used mostly because I did not have a good understanding of the project context.

In ng2014games (aka the project context)

mysql> describe project_teams;
+------------+-------------+------+-----+---------+----------------+
| Field      | Type        | Null | Key | Default | Extra          |
+------------+-------------+------+-----+---------+----------------+
| id         | int(11)     | NO   | PRI | NULL    | auto_increment |
| keyx       | varchar(80) | NO   | UNI | NULL    |                |
| role       | varchar(20) | NO   |     | NULL    |                |
| num        | int(11)     | NO   |     | NULL    |                |
| name       | varchar(80) | YES  |     | NULL    |                |
| coach      | varchar(80) | YES  |     | NULL    |                |
| points     | int(11)     | YES  |     | NULL    |                |
| orgKey     | varchar(80) | YES  |     | NULL    |                |
| levelKey   | varchar(80) | NO   |     | NULL    |                |
| projectKey | varchar(80) | NO   | MUL | NULL    |                |
| status     | varchar(20) | YES  |     | NULL    |                |
+------------+-------------+------+-----+---------+----------------+

mysql> describe project_game_teams;
+------------+-------------+------+-----+---------+----------------+
| Field      | Type        | Null | Key | Default | Extra          |
+------------+-------------+------+-----+---------+----------------+
| id         | int(11)     | NO   | PRI | NULL    | auto_increment |
| slot       | int(11)     | NO   |     | NULL    |                |
| role       | varchar(20) | NO   |     | NULL    |                |
| levelKey   | varchar(80) | YES  |     | NULL    |                |
| groupSlot  | varchar(40) | YES  |     | NULL    |                |
| teamKey    | varchar(80) | YES  | MUL | NULL    |                |
| teamName   | varchar(80) | YES  |     | NULL    |                |
| teamPoints | int(11)     | YES  |     | NULL    |                |
| score      | int(11)     | YES  |     | NULL    |                |
| orgKey     | varchar(40) | YES  |     | NULL    |                |
| report     | longtext    | YES  |     | NULL    |                |
| status     | varchar(20) | YES  |     | NULL    |                |
| gameId     | int(11)     | NO   | MUL | NULL    |                |
+------------+-------------+------+-----+---------+----------------+

mysql> describe project_games;
+------------+-------------+------+-----+---------+----------------+
| Field      | Type        | Null | Key | Default | Extra          |
+------------+-------------+------+-----+---------+----------------+
| id         | int(11)     | NO   | PRI | NULL    | auto_increment |
| projectKey | varchar(80) | NO   | MUL | NULL    |                |
| num        | int(11)     | NO   |     | NULL    |                |
| role       | varchar(20) | NO   |     | NULL    |                |
| venueName  | varchar(40) | YES  |     | NULL    |                |
| fieldName  | varchar(40) | YES  |     | NULL    |                |
| levelKey   | varchar(80) | YES  |     | NULL    |                |
| groupType  | varchar(20) | YES  |     | NULL    |                |
| groupName  | varchar(20) | YES  |     | NULL    |                |
| dtBeg      | datetime    | NO   |     | NULL    |                |
| dtEnd      | datetime    | YES  |     | NULL    |                |
| link       | int(11)     | YES  |     | NULL    |                |
| orgKey     | varchar(40) | YES  |     | NULL    |                |
| report     | longtext    | YES  |     | NULL    |                |
| status     | varchar(20) | NO   |     | NULL    |                |
+------------+-------------+------+-----+---------+----------------+

mysql> select distinct groupType from games;
+-----------+
| groupType |
+-----------+
| PP        | Pool Play
| SOF       | Soccerfest
| FM        | Final Match
| QF        | Quarter Final U14B Core QF 3 C 1st vs A 2nd
| SF        | Semi-final
| VIP       |
+-----------+

groupName
  A,B,C,D - pool play
  01-12,13-24 - soccer fest
  1,2,3,4 Medal round games
  
There was no direct relation between project_team and project_game_team.
The project_game_team entity was self-contained with all the information needed 
to display itself as well as calculate standings.

The team key was used to link the two entities as well spreadsheets.

Neither had any link to project_level which made searching by age/gender/program awkward.
levelKey AYSO_U19G_Core Fed_AgeGender_Program
VIP was a function f gender since there wer no gaes involved?

One nice thing about not having a direct relation is that I did not have to worry about if a team had been assigned or not.

Medal Round Results
  Group: U14B Core QF 1	
  Slot:  A 1st vs B 2nd
  
Pool Play Results
  Pool D1 vs D5
  
Team and Game Schedule
  Group: U12G Core PP D
  Slot: D3 vs D6
  
It would be nice to have team number (1-24) be broken out as well as SAR and coach?

Pool play results did not include a column for age/gender/program.  
In other words, no group column.
Group was in the table header.

Want to make program optional if there is only one program.
