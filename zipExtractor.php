<?php
    if(empty($argv[1])) {
        echo "Please input file. Usage: php zipExtractor.php ~/file.zip \n";
    } else {
        unzip($argv[1]);
    }
    

        $structure = array();

    function unzip($zipPath) {
        global $tmpFolderName;
        global $stucture;
	$status = "good";
 //       echo "Your zip file is at $zipPath. \n";
        $zip = zip_open($zipPath);
        if(is_resource($zip)){
            mkTmpDir();
             while($zip_entry = zip_read($zip)){
                $entryName = zip_entry_name($zip_entry);
                //echo "entryname is $entryName\n";
                // catches all zipslip vunerability variations e.g. ../dir/a.file, dir/../a.file, ./dir/a.file, dir/./a.file and also /dir/a.file
                if ((strstr($entryName,'./')) || ($entryName[0] == '/')){
                    echo "Unzip Failed. ZipSlip Vunerablity threat found. \n";
                    $dir = 'tmp'.'/'. $tmpFolderName;
                    chmod_R($dir, 0666, 0777);
                    delTree($dir);
                    $status = "threat";
                    return false;
                }
                $end = substr($entryName, strlen($entryName)-1);
                if( $end !== '/'){
                    $names = explode('/', $entryName);

                    $namearr =& $structure;
                    for($i = 0; $i < count($names); $i++){
                        if(count($names) - 1 === $i){
                            $namearr[] = $names[$i];
                        }else{
                            $namearr =& $namearr[$names[$i]];
                        }
                    }
                    $fp = fopen('tmp'.'/'. $tmpFolderName .'/'.$entryName, "w");
                    if (zip_entry_open($zip, $zip_entry, "r")) {
                        $buf = zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));
                        fwrite($fp, "$buf");
                        zip_entry_close($zip_entry);
                        fclose($fp);
                    }
                }else{
                    $floder = 'tmp'.'/'. $tmpFolderName .'/'. $entryName;
                    if(!file_exists($floder)){
                        if(!mkdir($floder, 0777, true)){
                            echo "Error.";
                            return false;
                        }
                    }
                }
            }
            zip_close($zip);
        }else{
            echo "error - ZIP_DECOMPRESSION_FAIL.\n";
            return false;
        }

        if ($status !== "threat") {
            echo "Success! Unzip files found in tmp/$tmpFolderName/\n";
        }
    } 

    function mkTmpDir() {
        global $tmpFolderName;
        $tmpFolderName = uniqid();
        if(!mkdir('tmp'.'/'.$tmpFolderName)) {
            echo "Create tmp path failed.\n";
            return false;
        }
    }

function delTree($dir) { 
   $files = array_diff(scandir($dir), array('.','..')); 
    foreach ($files as $file) { 
      (is_dir("$dir/$file")) ? delTree("$dir/$file") : unlink("$dir/$file"); 
    } 
    return rmdir($dir); 
  }     


function chmod_R($path, $filemode, $dirmode) { 
    if (is_dir($path) ) { 
        if (!chmod($path, $dirmode)) { 
            $dirmode_str=decoct($dirmode); 
            print "Failed applying filemode '$dirmode_str' on directory '$path'\n"; 
            print "  `-> the directory '$path' will be skipped from recursive chmod\n"; 
            return; 
        } 
        $dh = opendir($path); 
        while (($file = readdir($dh)) !== false) { 
            if($file != '.' && $file != '..') {  // skip self and parent pointing directories 
                $fullpath = $path.'/'.$file; 
                chmod_R($fullpath, $filemode,$dirmode); 
            } 
        } 
        closedir($dh); 
    } else { 
        if (is_link($path)) { 
            print "link '$path' is skipped\n"; 
            return; 
        } 
        if (!chmod($path, $filemode)) { 
            $filemode_str=decoct($filemode); 
            print "Failed applying filemode '$filemode_str' on file '$path'\n"; 
            return; 
        } 
    } 
} 

?>
