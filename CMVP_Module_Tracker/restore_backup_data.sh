 
#Restore data files (HTML format) from backup LHI network drive to my Cloud Foundry Drive. Usually after a power-cycle on the CF drive will erase all the data there.
#Each HTML data file is snap-shot of a single day from the relevant CMVP website page.
#This will restore ESV, Active, and MIP certificate data. 
cp /var/vcap/data/LHI/ESV_Data/esv_cert_pull_backup/* /home/vcap/app/.bp-config/ESV_Data/esv_cert_pull_backup/.
cp /var/vcap/data/LHI/Module_Active_Data/active_cert_pull_backup/* /home/vcap/app/.bp-config/Module_Active_Data/active_cert_pull_backup/.
cp /var/vcap/data/LHI/Module_In_Process_Data/cmvp_website_pull_backup/* /home/vcap/app/.bp-config/Module_In_Process_Data/cmvp_website_pull_backup/.
