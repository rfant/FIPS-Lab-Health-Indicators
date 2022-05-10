#used to get the latest Active Module  list from CMVP
#this will include cert_numbers, vendor, module name, lab name, sunset, etc.

# Steps how to use this script:
#
#	


MY_TOOL_PATH="/Users/fant/CMVP_Module_Tracker/Module_Active_Data"
MY_LOCAL_PATH="/Users/fant/CMVP_Module_Tracker/Module_Active_Data/active_cert_pull"


today=`date '+%m_%d_%Y'`;
LOG_FILENAME="$MY_TOOL_PATH/results/$today.CMVP_pull_of_Active_Modules.log"
echo "LOG_FILENAME=$LOG_FILENAME"
rm $LOG_FILENAME
touch $LOG_FILENAME
#!/bin/bash

echo "Disconnect VPN:  " >>$LOG_FILENAME
osascript -e "tell application \"/Applications/Tunnelblick.app\"" -e "disconnect \"RichardFant2020_ext\"" -e "end tell"
#give 5 seconds to disconnect
sleep 5

echo "Updating my Active Module List (reading ALL the current & historic Certs, each Cert with its own individual HTML file)." >> $LOG_FILENAME
echo "cd $MY_LOCAL_PATH" >> $LOG_FILENAME
cd $MY_LOCAL_PATH


#read the raw source code of the CMVP website and pipe it into grep to get the individual URLS of the certificate numbers.
#echo "Read the raw html source" >> $LOG_FILENAME
#echo "     ls -a | curl | grep > urls.txt" >> $LOG_FILENAME

#
rm urls.txt
sleep 5
for i in {1..5000}
do
   #echo "Cert Num $i"
   echo "https://csrc.nist.gov/projects/cryptographic-module-validation-program/certificate/$i" >> urls.txt
done

#now save each individual certificate 
echo "Now save each individual certificate." >> $LOG_FILENAME
echo "     xargs -n 1 curle -o < complete_list_of_historic_active_urls.txt" >> $LOG_FILENAME
xargs -n 1 curl -O < urls.txt



echo "Finished CMVP Active_cert_pull"

echo "     cd $MY_TOOL_PATH" >> $LOG_FILENAME
cd $MY_TOOL_PATH

echo "launching  HTML parser for ALL these hthousands of Certs HTML files." >> $LOG_FILENAME
echo "     ./go" >> $LOG_FILENAME
./go










