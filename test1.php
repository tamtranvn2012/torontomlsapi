<?php

$rets_login_url = "http://rets.torontomls.net:6103/rets-treb3pv/server/login";
$rets_username = "D13gcn";
$rets_password = "Gc$4512";
$rets_user_agent = "UserAgent/1.0";
$rets_user_agent_password = "123456";
// use http://retsmd.com to help determine the SystemName of the DateTime field which
// designates when a record was last modified
$rets_modtimestamp_field = "ModTimeStamp";

// use http://retsmd.com to help determine the names of the classes you want to pull.
// these might be something like RE_1, RES, RESI, 1, etc.
$property_classes = array("ResidentialProperty");

// DateTime which is used to determine how far back to retrieve records.
// using a really old date so we can get everything
$previous_start_time = "2013-01-01T00:00:00";

//////////////////////////////

require_once("phrets.php");

// start rets connection
$rets = new phRETS;

// only enable this if you know the server supports the optional RETS feature called 'Offset'
$rets->SetParam("offset_support", true);

echo "+ Connecting to {$rets_login_url} as {$rets_username}<br>\n";
$connect = $rets->Connect($rets_login_url, $rets_username, $rets_password);

if ($connect) {
        echo "  + Connected<br>\n";
}
else {
        echo "  + Not connected:<br>\n";
        print_r($rets->Error());
        exit;
}
$fields = $rets->GetMetadataTable("Property", "ResidentialProperty");
echo '<pre>';
//var_dump($fields);exit;
$query = "({'Area_code'}={'01'}+)";
$search = $rets->SearchQuery("Property", "ResidentialProperty", "(Area_code=01)", array('Limit' => 10));
while ($listingx = $rets->FetchRow($search)) {
	echo '<div>';
	echo 'Ad text: '.$listingx['Ad_text'].'<br/>';
	echo 'Address: '.$listingx['Addr'].'<br/>';
	echo '</div>';
	$mlnum = $listingx['Ml_num'];
	$count = 0;
	$photos = $rets->GetObject("Property", "Photo", $mlnum);
	foreach ($photos as $photo) {
		if ($count<=5){
			$listing = $photo['Content-ID'];
			$number = $photo['Object-ID'];
			$imgname = 'image-'.$listing.'-'.$number.'.jpg';
			if ($photo['Success'] == true) {
			//
					//file_put_contents("image-{$listing}-{$number}.jpg", $photo['Data']);
					file_put_contents("images/". $imgname, $photo['Data']);
					echo '<img src="'.$imgname.'" style="width:100px;"/>';
			}
			else {
					echo "({$listing}-{$number}): {$photo['ReplyCode']} = {$photo['ReplyText']}\n";
			}
			$count++;
		}
	}
	
}
echo '</pre>';
foreach ($property_classes as $class) {

        echo "+ Property:{$class}<br>\n";

        $file_name = strtolower("property_{$class}.csv");
        $fh = fopen($file_name, "w+");

        $fields_order = array();

        $query = "({$rets_modtimestamp_field}={$previous_start_time}+)";

        // run RETS search
        echo "   + Resource: Property   Class: {$class}   Query: {$query}<br>\n";
        //$search = $rets->SearchQuery("Property", $class, $query, array('Limit' => 200));
        $search = $rets->SearchQuery("Property", $class, $query, array('Limit' => 200));

        if ($rets->NumRows($search) > 0) {

                // print filename headers as first line
                $fields_order = $rets->SearchGetFields($search);
                fputcsv($fh, $fields_order);

                // process results
                while ($record = $rets->FetchRow($search)) {
                        $this_record = array();
                        foreach ($fields_order as $fo) {
                                $this_record[] = $record[$fo];
                        }
                        fputcsv($fh, $this_record);
                }

        }

        echo "    + Total found: {$rets->TotalRecordsFound($search)}<br>\n";

        $rets->FreeResult($search);

        fclose($fh);

        echo "  - done<br>\n";

}

echo "+ Disconnecting<br>\n";
$rets->Disconnect();