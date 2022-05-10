#used to get the latest MIP (module in process) list from CMVP
# Steps how to use this script:
#
#	1. Copy this script to the directory where your MIP raw data will 
#	   be stored.

MY_TOOL_PATH="/Users/fant/CMVP_Module_Tracker/Module_In_Process_Data/"
MY_current_LOCAL_PATH="/Users/fant/CMVP_Module_Tracker/Module_In_Process_Data/cmvp_website_pull"
MY_current_BACKUP_PATH="/Users/fant/CMVP_Module_Tracker/Module_In_Process_Data/cmvp_website_pull_backup"

MY_atsec_ONLY_LOCAL_PATH="/Users/fant/CMVP_Module_Tracker/Module_In_Process_Data/atsec_only_data"
MY_atsec_ONLY_BACKUP_PATH="/Users/fant/CMVP_Module_Tracker/Module_In_Process_Data/atsec_only_data_backup"

#!/bin/bash
#today=`date '+%m_%d_%Y'`;
today=`date '+%Y_%m_%d'`;

filename="$MY_current_LOCAL_PATH/$today.MIP.HTML"

LOG_FILENAME="$MY_TOOL_PATH/results/$today.CMVP_pull_of_MIP.log"
echo "LOG_FILENAME=$LOG_FILENAME"
rm $LOG_FILENAME
touch $LOG_FILENAME


# Disconnect the VPN before taking a snapshot of the CMVP MIP Webpage
echo "Disconnect VPN:  " >> $LOG_FILENAME
osascript -e "tell application \"/Applications/Tunnelblick.app\"" -e "disconnect \"RichardFant2020_ext\"" -e "end tell"
#give 10 seconds to disconnect
sleep 10

#--------- BACKUP OLD FILES --------------------------------------------------------------------------
echo "Move any current HTML MIP fles in $MY_current_LOCAL_PATH into the backup folder at $MY_current_BACKUP_PATH." >> $LOG_FILENAME
echo "     mv $MY_current_LOCAL_PATH/*.HTML $MY_current_BACKUP_PATH/." >> $LOG_FILENAME
cp $MY_current_LOCAL_PATH/*.HTML /Users/fant/CMVP_Module_Tracker/Module_In_Process_Data/cmvp_website_pull_backup_backup_just_in_case/
mv $MY_current_LOCAL_PATH/*.HTML $MY_current_BACKUP_PATH/.
echo " " >> $LOG_FILENAME

echo "Move any atsec Only HTML MIP fles in the atsec_Only_data into the backup folder ." >> $LOG_FILENAME
echo "     mv atsec_only to backup." >> $LOG_FILENAME
mv $MY_atsec_ONLY_LOCAL_PATH/*.HTM $MY_atsec_ONLY_BACKUP_PATH/.
echo " " >> $LOG_FILENAME


#---------------------------------------------------------------------------------------------------------
echo "Updating my atsec_Only CMVP Module In Process List" >> $LOG_FILENAME
echo "   TBD:pull from CST SVN (for now, just copy most recent file from backup)"  >> $LOG_FILENAME
cd $MY_atsec_ONLY_BACKUP_PATH
last_file=$(ls  -t1 |  head -n 1 )
cd $MY_TOOL_PATH

cp $MY_atsec_ONLY_BACKUP_PATH/$last_file $MY_atsec_ONLY_LOCAL_PATH
echo "    cp $MY_atsec_ONLY_BACKUP_PATH/$last_file $MY_atsec_ONLY_LOCAL_PATH" >> $LOG_FILENAME

echo " " >> $LOG_FILENAME


#----------------------------------------------------------------------------------------------------------------
echo "Updating my current CMVP Module In Process List (getting single HTML file from bar chart)." >> $LOG_FILENAME
echo "     curl https::Modules-In_Process-List > $filename" >> $LOG_FILENAME
#read the raw source code of the CMVP website and save it.
curl "https://csrc.nist.gov/Projects/cryptographic-module-validation-program/modules-in-process/Modules-In-Process-List" > $filename

#----------------------------------------------------------------------------------------------------------------
echo "Move any current HTML MIP fles in $MY_current_LOCAL_PATH into the backup folder at $MY_current_BACKUP_PATH." >> $LOG_FILENAME
echo "     mv $MY_current_LOCAL_PATH/*.HTML $MY_current_BACKUP_PATH/." >> $LOG_FILENAME
cp $MY_current_LOCAL_PATH/*.HTML /Users/fant/CMVP_Module_Tracker/Module_In_Process_Data/cmvp_website_pull_backup_backup_just_in_case/
#mv $MY_current_LOCAL_PATH/*.HTML $MY_current_BACKUP_PATH/.
cp $MY_current_LOCAL_PATH/*.HTML $MY_current_BACKUP_PATH/.
echo " " >> $LOG_FILENAME

#--------------------------------------------------------------------------------------------------------------
echo "Finished current & atsec_Only CMVP Module In Process (MIP) pull." >> $LOG_FILENAME
echo "     cd $MY_TOOL_PATH" >> $LOG_FILENAME
cd $MY_TOOL_PATH
echo "Launching './go' for HTML parser for this single HTML file." >> $LOG_FILENAME
echo "     ./go" >> $LOG_FILENAME

#parse these files now & insert the data into the sql database
./go







