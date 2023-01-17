<?php
$version = "0.01.21";
echo "starting updater v $version <br>\n";

function downloadUpdateZip($currentVersion, $saveTo){
    //The resource that we want to download.
    $fileUrl = "https://api.itsblue.de/updates/update.php?name=TFfoodplan&version=$currentVersion";
    
    //Open file handler.
    $fp = fopen($saveTo, 'w+');
    
    //If $fp is FALSE, something went wrong.
    if($fp === false){
        throw new Exception('Could not open: ' . $saveTo);
    }
    
    //Create a cURL handle.
    $ch = curl_init($fileUrl);
    
    //Pass our file handle to cURL.
    curl_setopt($ch, CURLOPT_FILE, $fp);
    
    //Timeout if the file doesn't download after 20 seconds.
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);
    
    //Execute the request.
    curl_exec($ch);
    
    //If there was an error, throw an Exception
    if(curl_errno($ch)){
        throw new Exception(curl_error($ch));
    }
    
    //Get the HTTP stanewTFftus code.
    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    //Close the cURL handler.
    curl_close($ch);

    return($statusCode);
}

// The path & filename to save to.
$updatePackage = './newTFfoodplan.zip';

// download the file
$statusCode = downloadUpdateZip($version, $updatePackage);

if( $statusCode == 200 ){
    // if the download was successfull
    echo "Download OK <br>\n";

    // assuming file.zip is in the same directory as the executing script.
    

    // get the absolute path to $updatePackage
    $path = pathinfo(realpath($updatePackage), PATHINFO_DIRNAME);

    $zip = new ZipArchive;
    $res = $zip->open($updatePackage);
    if ($res === TRUE) {
        // extract it to the path we determined above
        $zip->extractTo($path);
        $zip->close();
        echo "New Version installed successfully<br>\n";
        unlink($updatePackage);
    } 
    else {
        echo "Error opening the update package<br>\n";
	//unlink($updatePackage);
    }

}
else if($statusCode === 204) {
    echo "Up to date!";
}
else {
    echo "Error downloading the update package";
}
?>
