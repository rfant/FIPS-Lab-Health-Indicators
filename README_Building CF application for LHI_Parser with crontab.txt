01/13/2023
---------------------------------------
Part Alpha: (fips lab indicator)
To build CF application for LHI Indicators 
0) only use the CF web-based GUI for the indicator (for the LHI Parser, you'll use the CF cmd line since I have an cron job there)
1) After launching the CF portal (https://cloudfoundry.intel.com/apps/home) use:
	instances: 3
	disk: 512 MB
	memory: 512 MB
	Health Check Type: port
	staging timeout: 60 seconds
	running timeout: 1 second
	staging buildpack: https://github.com/cloudfoundry/php-buildpack#v4.4.68   (don't use the default since that pulls the lastest PHP which isn't compatible with ChartDirector)
	staging stack: cflinuxfs3 - Cloud Foundary Linux-based filesystem (Ubuntu 18.04)
2) restart/restage app
3) to update actual indicator PHP code, zip up file in windows, then
	select "App Code" / "Update" dropdown menu.   select zip file from above with PHP source code
4) you can SSH a terminal window using "SSH" button
-----------------------------------------
Part Bravo:(fips lab parser)
TO build CF application for LHI_Parser  with crontab
0) If you haven't done set yet, install the Cloud Foundry CLI.
	
	NOTE: DON'T USE THE CF Web-based GUI for the parser!! Instead only use windows CF command line and  ONly use the CF Web-based GUI for the Indicator itself
	NOTE3: logout of CF from terminal "cf logout"  and then log back in "cf login". Select "FIPS", "Development"
1) open CMD window on Intel Laptop running windows
2) cd /users/rfant/Downloads/cf-cron-master/cf-cron-master
3) if you haven't already:
	3a) make a directory called .bp-connect there and copy all the parser source code there (either from github individually, or from zip file in github "LHI_Parser_v*.zip" )
	3b) edit the "crontab" file located at /users/rfant/Downloads/cf-cron-master/cf-cron-master using standard crontab -e syntax inside the file
4) then type "cf push  fips-lab-parser "
5) If you have to recompile the parser executables, do that in the Ubuntu VM on laptop and copy the executables into the .bp-connect folder.

6) If you need to reconnect the shared network drive, see the file called "README_LHI NFS details.txt".


userful CF commands
a) cf apps		list all the apps associated with my account
b) cf restart fips-lab-parser  	where app-name is one of the listed in a)  NOTE: IP address will change each time you reboot.
c) cf push  fips-lab-parser	push all the yml packages from whatever directory you are in when you invoke this command.


//shared nework drive from within a SSH to the app
/var/vcap/data/LHI

//-------------------------------------------------------
Part Charlie:
//create  CF service to parser app

// use the same path name for the Mounting each time so that the path won't change after a reset
cf create-service smb Existing LHI-SERVICE-INSTANCE -c "{\"share\":\"//FIPSLHI-DM.cps.intel.com/fs_FIPSLHI\",\"username\":\"ad_rfant@intel.com\",\"password\":\"icSox1227Ted-fant\", \"mount\":\"/var/vcap/data/LHI\"}"

//now bind it to the CF parser app
cf bind-service fips-lab-parser LHI-SERVICE-INSTANCE


==================================
make sure the Faceless account permissions are setup correctly using windows explorer from Laptop
//FIPSLHI-DM.cps.intel.com/fs_FIPSLHI


================================
password change
0) change p/w for ad_rfant, sys_LHI, etc.
1) delete fips-lab-parser app from CF Web based tool. Unbind/delete the LHI-SERVICE-INSTANCE 
2) Create CF service  & bind it (see above Part Charlie above).  Using the new password.
1) then "cf push  fips-lab-parser "


