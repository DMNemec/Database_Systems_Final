<?php
function getServerPubIP($arg)
{
   $values= explode(':',$arg);
   $values=explode(')',$values[3]);
   return preg_replace('/\s+/', '', $values[0]);
}

function getServerPrivIP($arg)
{
   $values= explode(':',$arg);
   return preg_replace('/\s+/', '', $values[1]);
}

function getServerPort($arg)
{
   $values= explode(':',$arg);
   $port = explode('(', $values[2]);
   return preg_replace('/\s+/', '', $port[0]);
}


function getUID($arg)
{
    $values = explode('[U:',$arg);
    $uid=$values[1];
    $values = explode(']',$uid);
    $uid=$values[0];
    if (strlen($uid) == 0 )
        return "BOT";
    $uid = '[U:' . $uid . ']';
    return $uid;
}

function getPlayerName($arg)
{
    $values = explode(': "',$arg);
    $values = explode("\" killed \"",$values[1]);
    $player = $values[0];
    if (substr_count($player,'<') > 3)
    {
        $numToks = substr_count($player,'<');
        $offset = 0;
        for ($i=0;$i<3;$i++)
        {
            $offset = strrpos($player,"<");
            $player = substr($player,0,$offset);
        }
        return substr($player,0,$offset);
    }
    $values = explode('<',$player);
    $player = $values[0];
    return $player;
}

function uploadLog($arg){
    $target_dir = "uploads/";
    $target_file = $target_dir . basename($_FILES["fileToUpload"]["name"]);
    $uploadOk = 1;
    $filetype = pathinfo($target_file, PATHINFO_EXTENSION);

        //Check if file exists
    if (file_exists($target_file)) {
        echo "File already exists\n<br>";
        $uploadOk = 0;
    }

    //Check to verify it's a log file
    if($filetype != "log") {
        echo "Sorry, only log files are allowed.\n<br>";
        $uploadOk = 0;
    }

    //Check for errors
    if ($uploadOk == 0) {
        echo "Sorry, your file wasn't successfully uploaded.\n<br>";
    } else {
        if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
            echo "The file ". basename( $_FILES["fileToUpload"]["name"]). " has been uploaded.\n<br>";
        } else {
            echo "Sorry, there was an error uploading your file.\n<br>";
        }
    }
    return $target_file;
}


function getVictimName($arg)
{
    $values = explode('" killed "',$arg);
    $player = $values[1];
    if (substr_count($player,'<') > 3)
    {
        $numToks = substr_count($player,'<');
        $offset = 0;
        for ($i=0;$i<3;$i++)
        {
            $offset = strrpos($player,"<");
            $player = substr($player,0,$offset);
        }
        return substr($player,0,$offset);
    }
    $values = explode('<',$player);
    $player = $values[0];
    return $player;
}
function getVictimUID($arg)
{
    $values = explode('" killed "',$arg);
    $player = $values[1];
    if (substr_count($player,'<') > 3)
    {
        $offset = strrpos($player,"<");
        $player = substr($player,0,$offset-1);
        $offset = strrpos($player,"<")+1;
        return substr($player,$offset);
    }
    $values = explode('<',$player);
    $player = $values[2];
    return substr($player,0,strlen($player) -1);
}

function getPlayers($file)
{
    $idArray = array();
    $idPrefix = "[U:1:";
    fseek($file,0);
    while (($line = fgets($file)) !== false) {
        $lastPos = 0;
        if (preg_match ('/: Banid: /',$line)) continue;
        while (($lastPos = strpos($line, $idPrefix, $lastPos))!== false) {
                $idEnd = strpos($line,"]",$lastPos);
                $idArray[] = substr($line,$lastPos,$idEnd - $lastPos + 1);
                $lastPos = $idEnd + 1;
        }

    }
    $idArray = array_unique($idArray);
    return $idArray;

}

function getNotoriety($arg) {

    $notVals = substr($arg,60,-28);
    $notScore = intval($notVals);
    return $notScore;

}

function getTime($arg) {
    $headerLine = explode(': ',$arg); #array of 2+ parts
    $timeDateLine = $headerLine[0]; //isolate the time/date
    $timeDateLine = strtr($timeDateLine, '/', '-'); //replaces / with - in the date
    $timeDateLine = substr_replace($timeDateLine, ' ', 12, 3);//gets rid of the " - " in the date
    $timeDateLine = substr($timeDateLine, 2); //removes the "L " form the begining
    $timeDateLine = date_create($timeDateLine);
    $timeDateLine = date_format($timeDateLine, "Y-m-d H:i:s");
    echo "Time/Date Line: ". $timeDateLine."<br>";
}

function updateConnection($text, $array) {
    $player = getUID($text);
    if ($player == "BOT") {
        return;
    }
    echo "Player: ".$player."<br>";
    if (preg_match ('/connected, address/', $text)){
        $time = getTime($text);
        $array[$player] = $time;
    }
}

// The main program starts here

$handle = fopen(uploadLog($file), "r");
$debug = 5;
$database = "nemecd";

$link = mysqli_connect(ini_get("mysqli.default_host"),
                       ini_get("mysqli.default_user"),
                       ini_get("mysqli.default_pw"),
                       $database);

if(!$link) {
    printf("Can't connect to MySQL Server, database $database.  Errorcode: %\n<br>", mysqli_connect_error());
    exit;
} else {
printf("Successfully connected to MySQL Server, database $database.\n<br>");
}

if ($handle) {

    // Reset the input file to the beginning and get the Server properties
    fseek($handle,0);
    while (($line = fgets($handle)) !== false) {
        if (preg_match ('/udp\/ip  /',$line)) {
            $serverPrivIP = getServerPrivIP($line);
            getServerPubIP($line);
            $serverPort = getServerPort($line);
            if ($debug > 2) {
                echo "Server Private IP: " . getServerPrivIP($line). "\n<br>";
                echo "Server Public IP: " . getServerPubIP($line). "\n<br>";
                echo "Server Port: " . getServerPort($line). "\n<br>";
            }
            $serverIdQuery = 'select serverId from hlstats_Servers where address = "'.$serverPrivIP.'" and port = "'.$serverPort.'"';
            $serverIdResults = $link->query($serverIdQuery);
            if ($serverIdResults->num_rows > 0){
                $row = $serverIdResults->fetch_assoc();
                $serverId = $row["serverId"];
            } else {
                echo "Unable to retreive Server ID from database.<br>";
                exit;
            }
            if ($debug > 2) {
                echo "Server ID: ".$serverId."<br>";
            }
            break;
        }
    }

    // Reset the input file again.  Start reading in the match data
    fseek($handle,0);
    $locMatch = 0;
    if ($line = fgets($handle)) {
        // This outer loop iterates through each MATCH.
        // For each match, count the number of kills and deaths for
        // each player.
        do {
            $playerNameArray = array();
            $playerDeathArray = array();
            $playerKillArray = array();
            $playerTimeArray = array();
            $playerConnectArray = array();
            //    $playerArray = getPlayers($handle);
            $playerArray = array();

            do {
                // process the line read.
                if (preg_match ('/" committed suicide with "/',$line)) {
                    // echo "Suicide found: " . $line;
                    if ($debug > 3) {
                        echo "SUICIDE: " . getPlayerName($line) . " with UID " . getUID($line) . "\n<br>";
                    }
                    $playerDeathArray[getUID($line)]++;
                }
                elseif ( preg_match ('/" killed "/',$line) )
                {
                    if ($debug > 2) echo "Killer line: " . $line . "<br>";
                    $killer=getPlayerName($line);
                    $killerId = getUID($line);
                    $victim = getVictimName($line);
                    $victimUID = getVictimUID($line);
                    $playerNameArray[$killerId] = $killer;
                    $playerNameArray[$victimUID] = $victim;
                    $playerKillArray[$killerId]++;
                    $playerDeathArray[$victimUID]++;
                }
                elseif (preg_match ('/connect/',$line))
                {
                    if ($debug > 4) echo "(Dis)Connect line: " . $line . "<br>";
                    #need to do the connect/disconnect stuff here
                    updateConnection($line, $playerConnectArray);
                }

                elseif (preg_match ('/^======/',$line))
                {
                    $notPos = ftell($handle); //save starting position of scoretable
                    $line = fgets($handle); //column headers
                    if ($debug > 1) echo "Column Headers: " . $line . "<br>";
                    $matchDate = getTime($line); //I shouldn't have to explain this line
                    $line = fgets($handle);//priming input for loop
                    while (!preg_match ('/CHANGE LEVEL:/',$line) ) {
                        if ($debug > 1) echo "Score Line: " . $line . "<br>"; //should print the scores out
                        $line = fgets($handle);
                        //prevents reading second team's score twice in competative modes
                        if (preg_match ('/^======/',$line) or preg_match ('/rcon from/',$line)) {
                            break;
                        }
                        #next, add in the getNotoriety function here to process each line
                        #and filter out misc lines that get in here on accident
                    }
                    fseek($handle,$notPos); //reset file cursor to the column header line
                }
                $line = fgets($handle);
            } while (!preg_match ('/CHANGE LEVEL:/',$line) && $line = fgets($handle));
            if ($debug > 0) echo "End of match $locMatch\n<br>";
            if ($debug > 1) {
                print_r($playerNameArray);
                print "\n<br>";
                $keys = array_keys($playerKillArray);
                foreach($keys as $key)
                {
                    if($playerKillArray[$key] != 0)
                    {
                        print "playerKillArray[$key] = $playerKillArray[$key]\n<br>";
                    }
                }
                print "\n<br>";
                $keys = array_keys($playerDeathArray);
                foreach($keys as $key)
                {
                    if($playerDeathArray[$key] != 0)
                    {
                        print "playerDeathArray[$key] = $playerDeathArray[$key]\n<br>";
                    }
                }
                $keys = array_keys($playerConnectArray);
                foreach($keys as $key) {
                    if($playerConnectArray[$key] != 0) {
                        print "playerConnectArray[$key] = $playerConnectArray[$key]\n<br>";
                    }
                }
            }

            $locMatch++;
        } while ($line);
    }
    fclose($handle);
} else {
    // error opening the file.
}
?>
