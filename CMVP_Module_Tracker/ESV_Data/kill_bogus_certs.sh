MY_TOOL_PATH="/home/rfant/CMVP_Module_Tracker/Module_Active_Data"
MY_LOCAL_PATH="/home/rfant/CMVP_Module_Tracker/Module_Active_Data/active_cert_pull"
KILL_FILENAME= "$MY_LOCAL_PATH/kill.txt"

cd $MY_LOCAL_PATH
#grep -lrIZ 'Page Not Found' . > kill.txt
cat kill.txt
echo "Delete those bogus certifications: while read file; do rm ; done < kill.txt " 
while read file; do rm "$file"; done < kill.txt

#rm kill.txt

cd $MY_TOOL_PATH

