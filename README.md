# FIPS-Lab-Health-Indicators
Indicators using publicly available US Government data to show how well a FIPS Lab is performing


Nov 10, 2021
--------------------------------------------------------------
Here's how it works:

1) On a daily basis a cron job pulls the most recent CMVP Active data down from the CMVP website and saves as numerous html files.
Each Active CMVP Certificate will have its own file. All these html files are then parsed  using 'active_to_sql' which then updates the sql table 'CMVP_Active_Table'. The parser checks for dups, errors, etc.

2) Similarly, on a daily basis another cron job pulls the most recent CMVP Module In Process data from CMVP and saves it as a single html file.
This single html file has all a listing of all the Module in process and is parsed using 'current_mip_to_sql' which then updates the sql table
'CMPV_MIP_Table'. The parser checks fro dups, errors, etc.

--------------------------------------------------------------------
Folder Structure

The Module_In_Process_Date folder is used to:
	1) update the sql table: CMVP_MIP_Table  with the most recent daily data 
	   from  the CMPV website https://csrc.nist.gov/Projects/cryptographic-module-validation-program/modules-in-process/Modules-In-Process-List 
	
The Active_Module_Data folder is used to:
	1) Update the sql table: CMVP_Active_Table with the most recent daily data
		from the CMVP website 	https://csrc.nist.gov/projects/cryptographic-module-validation-program/validated-modules/search?SearchMode=Basic&CertificateStatus=Active&ValidationYear=0


The files/folders used are:
-----------------------------
Module_Active_Data folder
	update_active_list.sh
		1) Used to pull current active module data from https://csrc.nist.gov/projects/cryptographic-module-validation-program/validated-modules/search?SearchMode=Basic&CertificateStatus=Active&ValidationYear=0
		2) Note: to improve download speed, the external VPN is disabled during the CMVP pull
	
	active_cert_pull/
	
		1) folder containing all the currently active certificates. One html file for each cert.
	
	active_cert_pull_backup/
		1) a backup, just in case.
	
	active_to_sql
		1) executable that parses the html file and update the corresponding sql table.
	
	active_to_sql.ccp
		1) source code
	
	go
		1) shell script which invokes the parser after backing up the cert files.  This script is invoked by a cron job
	
	Makefile
		1) used to create active_to_sql executable.
	
	urls.txt
		1) temp file created by crawling through the CMVP active website. It is used to store all the certificate urls.
	
	utils.h
		1) contains file utilities used by 'active_to_sql'		

------------------------------
Module_In_Process_Data folder
	update_mip_list.sh  		
		1) Used to get the latest MIP data 
		from "https://csrc.nist.gov/Projects/cryptographic-module-validation-program/modules-in-process/Modules-In-Process-List" 
		3) Note: to improve download speed, the external VPN is disabled during the CMVP pull, but must be enabled
		during the CST SVN pull.
	
	cmvp_web site_pull/
		1) folder containing the most recent pull from the CMVP MIP website.
	
	curret_MIP_Indicator_sql.h
		1) include file used by the parser executable. This file contains most of the sql commands used by the parser 
		to update, merge duplicate, check errors, etc.
	
	current_MIP_indicator
		1) executable that parsers the html files and updates the "current" MIPS sql table.
	
	current_mip_to_sql.cpp
		1) source code for the "current data" parser
	
	
	
	MIP_Indicator_sql.h
		1) include file used by the parser executable for historic files. This file contains most of the sql commands used by the parser 
		to update, merge duplicate, check errors, etc.

	
	go
		1) shell script which invokes both parsers (current / historic). This script is invoked by the cron job.
	
	Makefile
		1) used for compiling both parsers (current/historic).
	
	utils.h
		1) contains file utilities used by both parsers (current/historic)


=========================

================================================================================================================
Actual Indicators
The indicators are written in PHP using the ChartDirector library. Intel has a license for this alreday, but the "free version" banner still appears
on the indicator.

The source code lives on the ubuntu VM at :
var/www/html/ChartDirector

All the PHP source files used by this indicator are prefaced with "cmvp_".  All the other PHP source files are there as examples of potential future
indicators.




