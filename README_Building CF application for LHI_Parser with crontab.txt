12/27/2022


Part Alpha:
TO build CF application for LHI_Parser with crontab
0) If you haven't done set yet, install the Cloud Foundry CLI.
	
	NOTE: DON'T USE THE CF Web-based GUI for the parser!! Instead only use windows CF command line and  ONly use the CF Web-based GUI for the Indicator itself
	NOTE2:	For the LHI indicator (CF web-based GUI), use buildpack: https://github.com/cloudfoundry/php-buildpack#v4.4.68     Stack:cflinuxfs3 - Cloud Foundary Linux-based filesystem (Ubuntu 18.04)
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

//=================================================================================
Part Bravo:
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
2) Create CF service using option2 and bind it.  Using the new password.
1) then "cf push  fips-lab-parser "
