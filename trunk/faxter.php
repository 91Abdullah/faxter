#!/usr/bin/php -q
<?php
require_once('/path/to/MimeMailParser.class.php');
require_once('/path/to/phpagi-asmanager.php');
require("/path/to/phpmailer.inc.php");

function event_test($e, $parameters, $server, $port)
{
	//print_r($parameters);
	global $unqid;
	global $faxstatus;
}
function hangupevent($e,$parameters,$server,$port)
{
	echo "new hangup";
	global $unqid;
	global $faxstatus;
	global $faxerror;
	echo $unqid;
	echo $e;
	if($parameters['Uniqueid']=="$unqid")
	{
		global $asm;
		global $outfile;
		global $from;
		echo "hangup received";
		echo $faxstatus;
		echo $faxerror;
		$mail = new phpmailer;
		echo "from";
		echo $from;
		$mail->IsSMTP(); // set mailer to use SMTP
		$mail->From = "fax@from.com";
		$mail->FromName = "fax Report";
		$mail->Host = "host ip";  // specify main and backup server
		$from=preg_replace('/^.+</','',$from);
		$from=preg_replace('/>$/','',$from);
		$mail->AddAddress($from);   // name is optional
		$mail->WordWrap = 50;    // set word wrap
		$mail->AddAttachment($outfile);
		openlog("messages", LOG_PID | LOG_PERROR, LOG_LOCAL0);
		syslog(LOG_WARNING,"$from");	
		$mail->IsHTML(true);    // set email format to HTML
		$mail->Subject = "$faxstatus";
		$mail->Body = "$faxerror";
		$mail->Send(); // send message
		$asm->disconnect();
		exit;
	}
}
function newaccountcode($e, $parameters, $server, $port)
{
        //print_r($parameters);
	global $acc;
        if($parameters['AccountCode']=="$acc" and $e=="newaccountcode")
        {
                global $unqid;
		global $chan;
                $unqid=$parameters['Uniqueid'];
                $chan=$parameters['Channel'];
		echo "newaccount";
	//	echo $uniqid;
        }
}
function setvar($e, $parameters, $server, $port)
{
	global $unqid;
	if($parameters['Uniqueid']=="$unqid")
	{
		global $faxstatus;
		global $faxerror;
		if($parameters['Variable']=="FAXSTATUS")
		{
			$faxstatus=$parameters['Value'];
		}
		if($parameters['Variable']=="FAXERROR")
                {
                        $faxerror=$parameters['Value'];
                }
	}
}
function faxsent($e, $parameters, $server, $port)
{
        global $unqid;
	print_r($parameters);
	if($parameters['Uniqueid']=="$unqid")
        {
		print_r($parameters);
	}
}

$data = file_get_contents("php://stdin");
$randnum=mt_rand(10000,99999);
$now=date("ymd_his");
$myfile = "/tmp/mail"."$now"."$randnum";
$fh = fopen($myfile, 'w') or die("can't open file");
//$stringData = "Bobby Bopper\n";
fwrite($fh, $data);
fclose($fh);
$path = $myfile;
chmod($myfile, 0777);


openlog("messages", LOG_PID | LOG_PERROR, LOG_LOCAL0);
global $from;
global $outfile;
//$path="/tmp/mail110124_08122537617";
//$myfile=$path;
//$randnum=mt_rand(10000,99999);
$Parser = new MimeMailParser();
$Parser->setPath($path);
//print_r($Parser);
$to = $Parser->getHeader('x-original-to');
$newto = explode("@",$to);
$to = $newto[0];
$from = $Parser->getHeader('from');
$subject = $Parser->getHeader('subject');
$text = $Parser->getMessageBody('text');
$html = $Parser->getMessageBody('html');
$attachments = $Parser->getAttachments();
//print_r($attachments);


$in="";
echo $to;
$save_dir = '/tmp/';
$count_file=0;
$name=substr(md5($randnum),1,8);
foreach($attachments as $attachment) {
  $count_file++;
  // get the attachment name
  $filename = $attachment->filename;
  // write the file to the directory you want to save it in
  $filename=strtolower($filename);
  if(preg_match('/pdf$/',$filename))
  {
        $filename=$name."$count_file".".pdf";
        $in=$in." ".$save_dir.$filename;
        if ($fp = fopen($save_dir.$filename, 'w'))
        {
                while($bytes = $attachment->read())
                {
                        fwrite($fp, $bytes);
                }
                fclose($fp);

          }
  }

  elseif(preg_match('/doc$/',$filename))
  {
	echo "found doc\n";
	$filename=$name."$count_file".".doc";
	//echo $filename;
	//echo "\n";
	if ($fp = fopen($save_dir.$filename, 'w+'))
        {
		syslog(LOG_WARNING, "$filename");
                while($bytes = $attachment->read())
                {
                        fwrite($fp, $bytes);
                }
                fclose($fp);
		chmod($save_dir.$filename,0777);

          }
   	echo $save_dir;
   	$doccom="/usr/bin/faxconvert.sh $save_dir"."$filename |logger";
	$o=system($doccom);
	syslog(LOG_WARNING, "$doccom"."$o");
	echo $doccom;	
	echo "\n";
	//$preg_replace(
        $newfilename=$name."$count_file".".pdf";
        if(file_exists("$save_dir"."$newfilename"))
	{
		chmod("$save_dir"."$newfilename",0777);
		echo "found doc to pdf file";
		$in=$in." ".$save_dir.$newfilename;
		echo "88888888888";
		echo $in;
	}
	else
	{
		echo "not found doc to pdf file";
	}
  }
  elseif(preg_match('/xls$/',$filename))
  {
        echo "found doc\n";
        $filename=$name."$count_file".".xls";
        //echo $filename;
        //echo "\n";
        if ($fp = fopen($save_dir.$filename, 'w+'))
        {
                syslog(LOG_WARNING, "$filename");
                while($bytes = $attachment->read())
                {
                        fwrite($fp, $bytes);
                }
                fclose($fp);
                chmod($save_dir.$filename,0777);

          }
        echo $save_dir;
        $doccom="/usr/bin/faxconvert.sh $save_dir"."$filename |logger";
        $o=system($doccom);
        syslog(LOG_WARNING, "$doccom"."$o");
        echo $doccom;
        echo "\n";
        //$preg_replace(
        $newfilename=$name."$count_file".".pdf";
        if(file_exists("$save_dir"."$newfilename"))
        {
                chmod("$save_dir"."$newfilename",0777);
                echo "found xls to pdf file";
                $in=$in." ".$save_dir.$newfilename;
                echo "88888888888";
                echo $in;
        }
        else
        {
                echo "not found doc to pdf file";
        }
  }
  elseif(preg_match('/jpeg$/',$filename) or preg_match('/jpg$/',$filename))
  {
        echo "found doc\n";
        $filename=$name."$count_file".".jpg";
        //echo $filename;
        //echo "\n";
        if ($fp = fopen($save_dir.$filename, 'w+'))
        {
                syslog(LOG_WARNING, "$filename");
                while($bytes = $attachment->read())
                {
                        fwrite($fp, $bytes);
                }
                fclose($fp);
                chmod($save_dir.$filename,0777);

          }
        echo $save_dir;
        $doccom="/usr/bin/faxconvert.sh $save_dir"."$filename |logger";
        $o=system($doccom);
        syslog(LOG_WARNING, "$doccom"."$o");
        echo $doccom;
        echo "\n";
        //$preg_replace(
        $newfilename=$name."$count_file".".pdf";
        if(file_exists("$save_dir"."$newfilename"))
        {
                chmod("$save_dir"."$newfilename",0777);
                echo "found jpg to pdf file";
                $in=$in." ".$save_dir.$newfilename;
                echo "88888888888";
                echo $in;
        }
        else
        {
                echo "not found doc to pdf file";
        }
  }
}
$outfile=substr(md5($myfile),1,10);
$outfile="/tmp/".$outfile.".tiff";
chmod($outfile,0755);
$com="/usr/bin/gs -sDEVICE=tiffg3 -dNOPAUSE -r204x98 -sOutputFile=$outfile -f $in -c quit";
system($com);
echo $com;
chmod($outfile,0777);
//exit;
//gs -sDEVICE=tiffg4 -dNOPAUSE -r203x98 -sOutputFile=/tmp/a.tiff -f a.pdf a.pdf -c quit
echo "0000";
echo $outfile;
global $asm;
global $unqid;
global $acc;
global $chan;
global $faxstatus;
global $faxerror;
$acc=substr(md5(rand(10000,99999)*rand(10000,99999)),1,8);
$asm = NEW AGI_AsteriskManager();
$asm->connect('localhost','admin','elastix456');
//$call=$asm->Originate("DAHDI/1/88575641","Application=txfax,Data=/tmp/Fax00000000.TIF");
$asm->add_event_handler('*','event_test');
$asm->add_event_handler('NewAccountCode','newaccountcode');
$asm->add_event_handler('Hangup','hangupevent');
$asm->add_event_handler('VarSet','setvar');
$asm->add_event_handler('faxsent','faxsent');
//$asm->add_event_handler('NewAccountCode','newaccountcode');
$call = $asm->send_request('Originate',
             array('Channel'=>"SIP/$to"."@pri",
                   'Application'=>"SendFAX",
		   'Account'=>"$acc",
                   'Data'=>"$outfile"));
$asm->wait_response();
//sleep(30);
$asm->disconnect();

?>
