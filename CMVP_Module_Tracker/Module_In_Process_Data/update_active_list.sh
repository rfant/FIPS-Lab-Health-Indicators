#!/usr/bin/bash

#used to get the latest Active Module  list from CMVP
#this will include cert_numbers, vendor, module name, lab name, sunset, etc.



MY_TOOL_PATH="/home/vcap/app/.bp-config/Module_Active_Data"
MY_LOCAL_PATH="/home/vcap/app/.bp-config/Module_Active_Data/active_cert_pull"
MY_BACKUP_PATH="/var/vcap/data/LHI/Module_Active_Data"
#MY_TOOL_PATH="/home/rfant/CMVP_Module_Tracker/Module_Active_Data"
#MY_LOCAL_PATH="/home/rfant/CMVP_Module_Tracker/Module_Active_Data/active_cert_pull"


today=`date '+%m_%d_%Y'`;
LOG_FILENAME="$MY_TOOL_PATH/results/$today.CMVP_pull_of_Active_Modules.log"
URL_FILENAME="$MY_LOCAL_PATH/urls.txt"
KILL_FILENAME= "$MY_LOCAL_PATH/kill.txt"

echo "LOG_FILENAME=$LOG_FILENAME"
rm -f $LOG_FILENAME
touch $LOG_FILENAME


#echo "Disconnect VPN:  " >>$LOG_FILENAME
#osascript -e "tell application \"/Applications/Tunnelblick.app\"" -e "disconnect \"RichardFant2020_ext\"" -e "end tell"
#give 5 seconds to disconnect
#sleep 5

echo "Updating my Active Module List (reading ALL the current & historic Certs, each Cert with its own individual HTML file)." >> $LOG_FILENAME
echo "cd $MY_LOCAL_PATH" >> $LOG_FILENAME
cd $MY_LOCAL_PATH


#Instead of pulling the Active & Historic certs seperately, I will instead  pull all certs sequentially from 1 to 6000. 
#Some of these cert numbers are bogus since the number was skipped or is in the future.
#But, this way, I get all Active and Historic and Revoked certificates in one fell swoop.
#I'll then clean up all dupe cert files and delete any bogus cert files.
rm -r $URL_FILENAME

for i in {1..6000}  #fix up my sequential cert list. see above comment.
do
  echo "https://csrc.nist.gov/projects/cryptographic-module-validation-program/certificate/$i" >> $URL_FILENAME
done
#--------------------------------------------------------------------
#now save each individual certificate whether it's valid or bogus.
echo "Save each individual certificate." >> $LOG_FILENAME
echo "     xargs -n 1 curle -o < urls.txt" >> $LOG_FILENAME
cd $MY_LOCAL_PATH

xargs -n 1 curl -O < $URL_FILENAME

#-------------------------------------------------------------------
#delete all "Page Not Found" files since they are bogus cert files.
#grep -lrIZ 'Page Not Found' . > $KILL_FILENAME
#cat $KILL_FILENAME
#echo "Delete those bogus certifications " 
#while read file; do rm -f "$file"; done < $KILL_FILENAME

cd $MY_TOOL_PATH


#-------------------
#delete all dups that may occur from pulling duplicate certifcate files on different dates.
#echo "Find all dupes and delete: fdupes -dN /Users/fant/CMVP_Module_Tracker/Module_Active_Data/active_cert_pull">> $LOG_FILENAME
#fdupes -dN /Users/fant/CMVP_Module_Tracker/Module_Active_Data/active_cert_pull >> $LOG_FILENAME
#----------------


# backup all my active cert data to the network share drive
#cp $MY_TOOL_PATH/active_cert_pull/* $MY_BACKUP_PATH/active_cert_pull_backup/.



# now parse the all the files 
echo "Finished CMVP Active_cert_pull">> $LOG_FILENAME



echo "launching  HTML parser for ALL these thousands of Certs HTML files." >> $LOG_FILENAME
echo "     ./go" >> $LOG_FILENAME
cd $MY_TOOL_PATH
./go









