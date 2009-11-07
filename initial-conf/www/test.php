#!/bin/php
<?php 
/*
	Northshore Software Header
*/

require("guiconfig.inc");

if ($_POST) {

	$form = $_POST['formname'];

	switch($form) {
		case "system_advanced":
			unset($input_errors);

			/* input validation */
			if (!$input_errors) {
				$config['system']['disableconsolemenu'] = $_POST['disableconsolemenu'] ? true : false;
				$config['system']['disablefirmwarecheck'] = $_POST['disablefirmwarecheck'] ? true : false;
				$config['system']['webgui']['expanddiags'] = $_POST['expanddiags'] ? true : false;
				$config['system']['webgui']['noantilockout'] = $_POST['noantilockout'] ? true : false;
				$config['filter']['bypassstaticroutes'] = $_POST['bypassstaticroutes'] ? true : false;
		
				write_config();
	 			push_config('networking');	
			}
		
			$retval = 0;
			if (!file_exists($d_sysrebootreqd_path)) {
				config_lock();
				$retval = filter_configure();
				$retval |= system_polling_configure();
				$retval |= system_set_termcap();
				config_unlock();
			}
			$savemsg = get_std_save_message($retval);
			if ($retval == 0) {
				sleep(2);
				echo '<!-- SUBMITSUCCESS --><center>Configuration saved successfully</center>';
                        }
			return $retval;
		case "system_general":
			unset($input_errors);

			/* input validation */
			$reqdfields = split(" ", "hostname domain username");
			$reqdfieldsn = split(",", "Hostname,Domain,Username");
	
			do_input_validation($_POST, $reqdfields, $reqdfieldsn, &$input_errors);
	
			if ($_POST['hostname'] && !is_hostname($_POST['hostname']))
				$input_errors[] = "The hostname may only contain the characters a-z, 0-9 and '-'.";
			if ($_POST['domain'] && !is_domain($_POST['domain']))
				$input_errors[] = "The domain may only contain the characters a-z, 0-9, '-' and '.'.";
			if (($_POST['dns1'] && !is_ipaddr($_POST['dns1'])) || ($_POST['dns2'] && !is_ipaddr($_POST['dns2'])) || ($_POST['dns3'] && !is_ipaddr($_POST['dns3'])))
				$input_errors[] = "A valid IP address must be specified for the primary/secondary/tertiary DNS server.";
			if ($_POST['username'] && !preg_match("/^[a-zA-Z0-9]*$/", $_POST['username']))
				$input_errors[] = "The username may only contain the characters a-z, A-Z and 0-9.";
			if ($_POST['webguiport'] && (!is_numericint($_POST['webguiport']) || 
				($_POST['webguiport'] < 1) || ($_POST['webguiport'] > 65535)))
				$input_errors[] = "A valid TCP/IP port must be specified for the webGUI port.";
			if (($_POST['password']) && ($_POST['password'] != $_POST['password2']))
				$input_errors[] = "The passwords do not match.";
	
			$t = (int)$_POST['timeupdateinterval'];
			if (($t < 0) || (($t > 0) && ($t < 6)) || ($t > 1440))
				$input_errors[] = "The time update interval must be either 0 (disabled) or between 6 and 1440.";
			foreach (explode(' ', $_POST['timeservers']) as $ts) {
				if (!is_domain($ts))
					$input_errors[] = "A NTP Time Server name may only contain the characters a-z, 0-9, '-' and '.'.";
			}

			if (!$input_errors) {
				$config['system']['hostname'] = strtolower($_POST['hostname']);
				$config['system']['general']['domain'] = strtolower($_POST['domain']);
				$oldwebguiproto = $config['system']['general']['webgui']['protocol'];
				$config['system']['username'] = $_POST['username'];
				$config['system']['general']['webgui']['protocol'] = $_POST['webguiproto'];
				$config['system']['general']['webgui']['certificate'] = $_POST['cert'];
				$oldwebguiport = $config['system']['general']['webgui']['port'];
				$config['system']['general']['webgui']['port'] = $_POST['webguiport'];
				$config['system']['general']['timezone'] = $_POST['timezone'];
				$config['system']['general']['timeservers'] = strtolower($_POST['timeservers']);
				$config['system']['general']['time-update-interval'] = $_POST['timeupdateinterval'];
		
				unset($config['system']['general']['dnsserver']);
				if ($_POST['dns1'])
					$config['system']['general']['dnsserver'][] = $_POST['dns1'];
				if ($_POST['dns2'])
					$config['system']['general']['dnsserver'][] = $_POST['dns2'];
				if ($_POST['dns3'])
					$config['system']['general']['dnsserver'][] = $_POST['dns3'];
		
				$olddnsallowoverride = $config['system']['general']['dnsallowoverride'];
				$config['system']['general']['dnsallowoverride'] = $_POST['dnsallowoverride'] ? true : false;
				$oldsshd = isset($config['system']['general']['sshd']['enabled']);	
				$config['system']['general']['sshd']['enabled'] = $_POST['sshdenabled'] ? true : false;
				$config['system']['general']['symon']['enabled'] = $_POST['symonenabled'] ? true : false;
                		if ($_POST['muxip'])
					$config['system']['general']['symon']['muxip'] = $_POST['muxip'];
				if ($_POST['password'])
					$config['system']['password'] = base64_encode($_POST['password']);
		
				write_config();
		
				if (($oldwebguiproto != $config['system']['general']['webgui']['protocol']) ||
					($oldwebguiport != $config['system']['general']['webgui']['port']) ||
					($oldsshd != isset($config['system']['general']['sshd']['enabled'])))
					touch($d_sysrebootreqd_path);
		
				$retval = 0;
		
				if (!file_exists($d_sysrebootreqd_path)) {
					config_lock();
					$retval = system_hostname_configure();
					$retval |= system_hosts_generate();
					$retval |= system_resolvconf_generate();
					$retval |= system_password_configure();
					$retval |= services_dnsmasq_configure();
					$retval |= system_timezone_configure();
 					$retval |= system_ntp_configure();
 			
		 			if ($olddnsallowoverride != $config['system']['general']['dnsallowoverride'])
 						$retval |= interfaces_wan_configure();
 			
					config_unlock();
				}
				if ($retval == 0) {
                                	sleep(2);
                                	echo '<center>Configuration saved successfully<br><INPUT TYPE="button" value="OK" onClick="hidediv(\'save_config\')"></center>';
                        	}
			} else {
				sleep(2);
                                        echo '<center>Errors were found<br>Configuration not saved<br>';
					print_input_errors($input_errors);
					echo '<INPUT TYPE="button" value="OK" onClick="hidediv(\'save_config\')"></center>';
			}
			return $retval;
		case "system_routes":
        		unset($input_errors);	
			if ($_POST['apply']) {
                		$retval = 0;
                		if (!file_exists($d_sysrebootreqd_path)) {
                        		$retval = system_routing_configure();
                        		$retval |= filter_configure();
                        		push_config('staticroutes');
                		}
                		$savemsg = get_std_save_message($retval);
                		if ($retval == 0) {
                        		if (file_exists($d_staticroutesdirty_path)) {
                                		config_lock();
                                		unlink($d_staticroutesdirty_path);
                                		config_unlock();
                        		}
                		}
        		}
			if ($retval == 0) {
                                sleep(2);
                                echo '<center>Configuration saved successfully<br><INPUT TYPE="button" value="OK" onClick="hidediv(\'save_config\')"></center>';
                        }
			return $retval;
		case "system_networking":
			unset($input_errors);

 			/* input validation */
  			if (!$input_errors) {
    				$config['system']['networking']['maxinputque'] = $_POST['maxinputque'];
    				$config['system']['networking']['maxicmperror'] = $_POST['maxicmperror'];
    				$config['system']['networking']['ackonpush'] = $_POST['ackonpush'] ? true : false;
    				$config['system']['networking']['ecn'] = $_POST['system']['ecn'] ? true : false;
    				$config['system']['networking']['tcpscaling'] = $_POST['tcpscaling'] ? true : false;
    				$config['system']['networking']['tcprcv'] = $_POST['tcprcv'];
                		$config['system']['networking']['tcpsnd'] = $_POST['tcpsnd'];
    				$config['system']['networking']['sack'] = $_POST['sack'] ? true : false;
    				$config['system']['networking']['udprcv'] = $_POST['udprcv'];
    				$config['system']['networking']['udpsnd'] = $_POST['udpsnd'];

 	   			write_config();
     				push_config('networking');

 	     			touch($d_sysrebootreqd_path);
    			}

    			$retval = 0;
    			if (!file_exists($d_sysrebootreqd_path)) {
      				config_lock();
      				$retval |= system_advancednetwork_configure();
      				config_unlock();
    			}
    			$savemsg = get_std_save_message($retval);
			if ($retval == 0) {
                                sleep(2);
                                echo '<center>Configuration saved successfully<br><INPUT TYPE="button" value="OK" onClick="hidediv(\'save_config\')"></center>';
                        }
			return $retval;	
		case "system_users":
        		unset($input_errors);

		        /* input validation */
       			if (isset($id) && ($a_user[$id])) {
                		$reqdfields = explode(" ", "username");
                		$reqdfieldsn = explode(",", "Username");
        		} else {
                		$reqdfields = explode(" ", "username password");
                		$reqdfieldsn = explode(",", "Username,Password");
        		}

        		do_input_validation($_POST, $reqdfields, $reqdfieldsn, &$input_errors);

 		       	if (preg_match("/[^a-zA-Z0-9\.\-_]/", $_POST['username']))
                       		$input_errors[] = "The username contains invalid characters.";

      	                if($_POST['username']==$config['system']['username'])
        	                $input_errors[] = "username can not match the administrator username!";

        		if (($_POST['password']) && ($_POST['password'] != $_POST['password2']))
                		$input_errors[] = "The passwords do not match.";

 		       if (!$input_errors && !(isset($id) && $a_user[$id])) {
                		/* make sure there are no dupes */
                		foreach ($a_user as $userent) {
                        		if ($userent['name'] == $_POST['username']) {
                                		$input_errors[] = "Another entry with the same username already exists.";
                                		break;
                        		}
                		}
        		}

 		        if (!$input_errors) {

 		                if (isset($id) && $a_user[$id])
        	 	                $userent = $a_user[$id];

 	                        $userent['name'] = $_POST['username'];
        	        		$userent['fullname'] = $_POST['fullname'];

 		               	if ($_POST['password'])
 		                       	$userent['password'] = base64_encode($_POST['password']);

 		               	if (isset($id) && $a_user[$id])
                	        	$a_user[$id] = $userent;
                		else
                        		$a_user[] = $userent;

 		               	write_config();
                		push_config('accounts');
				$retval = 0;
                        	$retval = system_password_configure();
                        	$savemsg = get_std_save_message($retval);

			}
			if ($retval == 0) {
                                sleep(2);
                                echo '<center>Configuration saved successfully<br><INPUT TYPE="button" value="OK" onClick="hidediv(\'save_config\')"></center>';
                        }
			return $retval;
		case "system_groups":
			unset($input_errors);

		        /* input validation */
 		        $reqdfields = explode(" ", "groupname");
   		        $reqdfieldsn = explode(",", "Group Name");

     		        do_input_validation($_POST, $reqdfields, $reqdfieldsn, &$input_errors);

 	                if (preg_match("/[^a-zA-Z0-9\.\-_ ]/", $_POST['groupname']))
                       		$input_errors[] = "The group name contains invalid characters.";

 		        if (!$input_errors && !(isset($id) && $a_group[$id])) {
                		/* make sure there are no dupes */
               		 	foreach ($a_group as $group) {
                        		if ($group['name'] == $_POST['groupname']) {
                                		$input_errors[] = "Another entry with the same group name already exists.";
                                		break;
                	        	}
                		}
        		}

  		        if (!$input_errors) {

 	                	if (isset($id) && $a_group[$id])
                        		$group = $a_group[$id];

 		               	$group['name'] = $_POST['groupname'];
                		$group['description'] = $_POST['description'];
                		unset($group['pages']);
                		foreach ($pages as $fname => $title) {
                        		$identifier = str_replace('.php','',$fname);
                        		if ($_POST[$identifier] == 'yes')
                                		$group['pages'][] = $fname;
                        	}
 	              	}

 	               if (isset($id) && $a_group[$id])
        	                $a_group[$id] = $group;
                	else
                        	$a_group[] = $group;

 	               write_config();
        	       push_config('accounts');
		       if ($retval == 0) {
                                sleep(2);
                                echo '<center>Configuration saved successfully<br><INPUT TYPE="button" value="OK" onClick="hidediv(\'save_config\')"></center>';
                        }
			return 0; 
		case "system_firmware":
			
        		if (stristr($_POST['Submit'], "Upgrade") || $_POST['sig_override'])
                		$mode = "upgrade";
        		else if ($_POST['sig_no'])
                		unlink("{$g['ftmp_path']}/firmware.img");

			if ($mode) {
                		if ($mode == "upgrade") {
                        		if (is_uploaded_file($_FILES['ulfile']['tmp_name'])) {
 		                               	/* verify firmware image(s) */
               		                 	if (!stristr($_FILES['ulfile']['name'], $g['fullplatform']) && !$_POST['sig_override'])
                        	                	$input_errors[] = "The uploaded image file is not for this platform ({$g['fullplatform']}).";
                                		else if (!file_exists($_FILES['ulfile']['tmp_name'])) {
                                        		/* probably out of memory for the MFS */
                                        		$input_errors[] = "Image upload failed (out of memory?)";
                                        		exec_rc_script("/etc/rc.firmware disable");
                                        		if (file_exists($d_fwupenabled_path))
                                                		unlink($d_fwupenabled_path);
                                		} else {
                                        		/* move the image so PHP won't delete it */
                                        		rename($_FILES['ulfile']['tmp_name'], "{$g['ftmp_path']}/firmware.img");
						}
					}
	                	}
			}
                         /* check digital signature */
$sigchk = verify_digital_signature("{$g['ftmp_path']}/firmware.img");
 
if ($sigchk == 1)
$sig_warning = "The digital signature on this image is invalid.";
else if ($sigchk == 2)
$sig_warning = "This image is not digitally signed.";
else if (($sigchk == 3) || ($sigchk == 4))
$sig_warning = "There has been an error verifying the signature on this image.";
 
if (!verify_gzip_file("{$g['ftmp_path']}/firmware.img")) {
$input_errors[] = "The image file is corrupt.";
unlink("{$g['ftmp_path']}/firmware.img");
}
                        if ($sig_warning) {
                               $sig_warning = "<strong>" . $sig_warning . "</strong><br>This means that the image you uploaded " .
"is not an official/supported image and may lead to unexpected behavior or security " .
"compromises. Only install images that come from sources that you trust, and make sure ".
"that the image has not been tampered with.<br><br>".
"Do you want to install this image anyway (on your own risk)?";
                               echo "<center>$sig_warning</center>";
                        echo '<script type="text/javascript">

// pre-submit callback
function showRequest(formData, jqForm, options) {
    displayProcessingDiv();
    return true;
}

// post-submit callback
function showResponse(responseText, statusText)  {
    if(responseText.match(/SUBMITSUCCESS/)) {
           setTimeout(function(){ $(\'#save_config\').fadeOut(\'slow\'); }, 2000);
    }
}

        // wait for the DOM to be loaded
    $(document).ready(function() {
            var options = {
                        target:        \'#save_config\',  // target element(s) to be updated with server response
                        beforeSubmit:  showRequest,  // pre-submit callback
                        success:       showResponse  // post-submit callback
            };

           // bind form using \'ajaxForm\'
           $(\'#iform\').ajaxForm(options);
    });
</script>
<form action="form_submit.php" method="post"><input name="sig_override" type="submit" class="formbtn" id="sig_override" value=" Yes ">
<input name="sig_no" type="submit" class="formbtn" id="sig_no" value=" No "></form>';
                        return 0;
                        }
                        echo '<center>File Upload Complete<br><INPUT TYPE="button" value="OK" name="SUBMITSUCCESS" onClick="hidediv(\'save_config\')"></center>';
			return 0;
			default;
 echo '<center>Unknown form submited!<br><INPUT TYPE="button" value="OK" name="SUBMITSUCCESS" onClick="hidediv(\'save_config\')"></center>';
			return 0;
	}
}
?>