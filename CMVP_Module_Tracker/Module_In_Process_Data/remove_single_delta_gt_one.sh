


#$diff -U 0 1-03_27_2021 1-03_28_2021 | grep -c ^@  
RAW_DATA_PATH="/Users/fant/CMVP_Module_Tracker/Module_Active_Data/active_cert_pull"
MY_TOOL_PATH="/Users/fant/CMVP_Module_Tracker/Module_Active_Data"

cd $RAW_DATA_PATH

#test 0 -eq "$#" compares (numerically, with -eq) "0" with "$#" (which is the number of arguments passed to the shell script). 
#If 0, there was no arguments passed, and then (&&) it creates an argument list with set -- * (* which will expand to all the 
#files and dirs in the current directory (as the script as no "cd" before that, it will be the directory the person was in when 
#launching the script, or the home-dir of the user starting the script if the script is launched remotely or via something like cron


# No args, use files in current directory
test 0 -eq $# && set -- *
i=0
j=1
for target_file in "$@" 
	do
    shift
    #echo outer loop number $i
	    for candidate_file in "$@" 
	    do

	        compare=$(diff -U 0 "$target_file" "$candidate_file" | grep -c ^@)
	       #echo "$compare  =  $target_file $candidate_file "
	        echo "$compare delta between $target_file $candidate_file" 
	        if (( $compare == 1))  
	        then
	        	#echo "$compare  =  $target_file $candidate_file "
	            echo "file $j: kill $candidate_file "
	            rm -f $candidate_file
	            ((j+=1))
	        fi
	        
	    done
    #((i+=1))
done
cd $MY_TOOL_PATH