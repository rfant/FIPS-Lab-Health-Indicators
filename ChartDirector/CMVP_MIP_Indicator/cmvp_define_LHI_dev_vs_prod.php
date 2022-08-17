
<?php
#Indicator: Define which postgresql database the LHI indicators pull from
#comment out one of these lines. 


//development
# ubuntu local Virtual Machine
/*
$PROD=2;
$URL_str="http://127.0.0.1/ChartDirector/CMVP_MIP_Indicator";  //development URL (my laptop is the server)
$URL_path="/ChartDirector/CMVP_MIP_Indicator";
// Store a string into the variable which need to be Encrypted
$ciphering = "AES-128-CTR";
$iv_length = openssl_cipher_iv_length($ciphering);
$options = 0;
$decryption_iv = '3141592653598'; // Non-NULL Initialization Vector for decryption
$decryption_key = "Sox86Ted-";  // Store the decryption key
*/


//----------------------------------


//production
#intel intranet prodution 
 
$PROD= 1;
$URL_str="fips-lab-indicator.apps1-fm-int.icloud.intel.com";
$URL_path="";

// Store a string into the variable which need to be Encrypted
$ciphering = "AES-128-CTR";
$options = 0;
$decryption_iv = '0003141592653598'; // Non-NULL Initialization Vector for decryption
$decryption_key = "Sox86Ted-";  // Store the decryption key



?>  