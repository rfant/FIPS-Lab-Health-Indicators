TO build CF application for LHI_Parser with crontab
0) If you haven't done set yet, install the Cloud Foundry CLI.
1) open CMD window on Intel Laptop running windows
2) cd /users/rfant/Downloads/cf-cron-master/cf-cron-master
3) if you haven't already:
	3a) make a directory called .bp-connect there and copy all the parser source code there
	3b) edit the "crontab" file located at /users/rfant/Downloads/cf-cron-master/cf-cron-master using standard crontab -e syntax inside the file
4) then type "cf push  fips-lab-parser "
5) If you have to recompile the parser executables, do that in the Ubuntu VM on laptop and copy the executables into the .bp-connect folder.




userful CF commands
a) cf apps		list all the apps associated with my account
b) cf restart app-name  	where app-name is one of the listed in a)  NOTE: IP address will change each time you reboot.
c) cf push  app-name	push all the yml packages from whatever directory you are in when you invoke this command.


//shared nework drive
/var/vcap/data/8a04fc41-3164-4df4-afe4-f3b1bcdfa2f5