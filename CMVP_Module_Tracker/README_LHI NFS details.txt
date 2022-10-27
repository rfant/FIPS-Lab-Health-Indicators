
Mount You SharedFiles

df -aTh
mount -l
cat /proc/mounts
df -HP -t nfs    //shows all nfs mounts
sudo fdisk -l

	
linux
	sudo mount -t cifs -o 'user=ad_rfant,domain=amr,vers=3.0' //FIPSLHI-DM.cps.intel.com/fs_FIPSLHI    /mnt/LHIdrive


windows
	
	net use z:  \\FIPSLHI-DM.cps.intel.com\fs_FIPSLHI

ping FIPSLHI-DM.cps.intel.com

======================================
//create  CF service to parser app

//option 1:use default mount path. Not a good idea since the final directory name will change each time your p/w changes
//cf create-service smb Existing  LHI-SERVICE-INSTANCE  -c "{\"share\":\"//FIPSLHI-DM.cps.intel.com/fs_FIPSLHI\",\"username\":\"ad_rfant@intel.com\",\"password\":\"icSox1003Ted-fant\"}"

//option 2: use the same path name for the Mounting
cf create-service smb Existing LHI-SERVICE-INSTANCE -c "{\"share\":\"//FIPSLHI-DM.cps.intel.com/fs_FIPSLHI\",\"username\":\"ad_rfant@intel.com\",\"password\":\"icSox1003Ted-fant\", \"mount\":\"/var/vcap/data/LHI\"}"


//now bind it to the CF parser app


cf bind-service fips-lab-parser LHI-SERVICE-INSTANCE

