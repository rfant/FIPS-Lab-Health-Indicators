
<?php
#Indicator: Define which postgresql database the LHI indicators pull from
#comment out one of these lines. 

# ubuntu local Virtual Machine
//$PROD=2;

#intel intranet prodution 
$PROD= 1;

#intel internet pre-prod development mode
//$PROD=  0;

// Store a string into the variable which need to be Encrypted
$ciphering = "AES-128-CTR";
$iv_length = openssl_cipher_iv_length($ciphering);
$options = 0;
  
$decryption_iv = '3141592653598'; // Non-NULL Initialization Vector for decryption
$decryption_key = "Sox86Ted-";  // Store the decryption key



?>  