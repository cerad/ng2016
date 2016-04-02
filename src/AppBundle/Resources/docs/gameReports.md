Game reports are actually complicated that they dederve their own complete structure.

    gameReport
      notes
      status
      teams
        homeTeamReport
          misconduct
        awayTeamReport
          misconduct
          
Each report would need to be linked to their respective game/team entities.

But I always cheat.  Someday I will implement a general report system but for now I just add a report property
to the game and gameTeam entities and store the report values as an array.  Can't query but it works well.

Problem with this approach is that it becomes a bit of a mess to pull out the necessary information to
calculate game points and team standings.  Fragile at best.

For ng2016 I got a bit better.  There is now a game report repository which returns a game report data object.
The gameReport object has teamReport entities.
The gameReport form now feeds off of the gameReport entity.
Likewise, the points calculator works from the game report entities.

Code seems a bit cleaner.

The pool standings uses the team reports.  Might need to be refined a bit.
