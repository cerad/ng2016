AYSO uses a 8 or 9 digit number to uniquely identity volunteers
There used to be a handful of people with two numbers but we ignore those
e3 is an online api that allows querying volunteers by their aysoid.

Not all volunteers are in e3.  There are about 100 past zayso volunteers whose aysoid is not in e3.
We use local data for those.

For each tournament, zayso stores
  fedPersonId aka fedKey aka aysoid
  fedOrgId aka orgKey aka sar or region
  cert info such as referee badge
  
We rely on e3 to provide current info
  the e3 referee badge will override what the user thinks their badge is
  the e3 sar will likewise override what the user says
    if the user has moved and e3 is not updated then e3 will still win
    not current provision for overriding the sar
    the sar is used by the assignor to check for game conflicts
    Giving the ability to override the sar is not there
    
Change region on registration form to sar
  A sar view is generated using a lookup transformer
  Unusual sars will be added to the lookup by hand
  e3 sars look like 5/C/0894
  it should also be possible to generate sar from region
  
Todo
  Find a place to store role can cert constants like CERT_REFEREE
  