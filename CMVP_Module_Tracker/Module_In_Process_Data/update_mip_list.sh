#!/usr/bin/bash

#used to get the latest MIP (module in process) list from CMVP
MY_TOOL_PATH="/home/rfant/CMVP_Module_Tracker/Module_In_Process_Data/"
MY_current_LOCAL_PATH="/home/rfant/CMVP_Module_Tracker/Module_In_Process_Data/cmvp_website_pull"
MY_current_BACKUP_PATH="/home/rfant/CMVP_Module_Tracker/Module_In_Process_Data/cmvp_website_pull_backup"



today=`date '+%Y_%m_%d'`;

FILENAME="$MY_current_LOCAL_PATH/$today.MIP.HTML"

LOG_FILENAME="$MY_TOOL_PATH/results/$today.CMVP_pull_of_MIP.log"
echo "LOG_FILENAME=$LOG_FILENAME"
rm -f $LOG_FILENAME
touch $LOG_FILENAME


# Disconnect the VPN before taking a snapshot of the CMVP MIP Webpage
#echo "Disconnect VPN:  " >> $LOG_FILENAME
#osascript -e "tell application \"/Applications/Tunnelblick.app\"" -e "disconnect \"RichardFant2020_ext\"" -e "end tell"
#give 10 seconds to disconnect
#sleep 10

#--------- BACKUP OLD FILES --------------------------------------------------------------------------
echo "Move any current HTML MIP fles in $MY_current_LOCAL_PATH into the backup folder at $MY_current_BACKUP_PATH." >> $LOG_FILENAME
echo "     mv $MY_current_LOCAL_PATH/*.HTML $MY_current_BACKUP_PATH/." >> $LOG_FILENAME
mv $MY_current_LOCAL_PATH/*.HTML $MY_current_BACKUP_PATH/.
echo " " >> $LOG_FILENAME


#----------------------------------------------------------------------------------------------------------------
echo "Updating my current CMVP Module In Process List (getting single HTML file from bar chart)." >> $LOG_FILENAME
echo "     curl https::Modules-In_Process-List > $FILENAME" >> $LOG_FILENAME
#read the raw source code of the CMVP website and save it.
curl "https://csrc.nist.gov/Projects/cryptographic-module-validation-program/modules-in-process/Modules-In-Process-List" > $FILENAME

#--------------------------------------------------------------------------------------------------------------
echo "Finished current  CMVP Module In Process (MIP) pull." >> $LOG_FILENAME
echo "     cd $MY_TOOL_PATH" >> $LOG_FILENAME
cd $MY_TOOL_PATH
echo "Launching './go' for HTML parser for this single HTML file." >> $LOG_FILENAME
echo "     ./go" >> $LOG_FILENAME

#parse these files now & insert the data into the sql database
./go







