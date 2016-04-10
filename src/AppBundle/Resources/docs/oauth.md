Restarting the oauth journey for ng2016.

Pulled down cerad/oauth2 which solves some of the guzzle related problems

For google, started with
https://developers.google.com/identity/protocols/OAuth2

Already had account on the developers site:
https://console.developers.google.com/apis/credentials?project=api-project-598858148500

Created a new set of credentials.  Googles allows adding multiple callback urls.

Downloaded a json file with various bits of info.

Not yet sure where scope comes in.

Need to deal with the public logout session somehow.

I use google to login in.  Next time I hit the connect button google allows the previous info to be valid.
Does not prompt the user again.  Need to force it to ask.

