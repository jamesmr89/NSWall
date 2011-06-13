#!/bin/php
<?php
/*
 $Id: system_usermanager.php
 part of m0n0wall (http://m0n0.ch/wall)

 Copyright (C) 2005 Paul Taylor <paultaylor@winn-dixie.com>.
 All rights reserved.

 Copyright (C) 2003-2005 Manuel Kasper <mk@neon1.net>.
 All rights reserved.

 Redistribution and use in source and binary forms, with or without
 modification, are permitted provided that the following conditions are met:

 1. Redistributions of source code must retain the above copyright notice,
 this list of conditions and the following disclaimer.

 2. Redistributions in binary form must reproduce the above copyright
 notice, this list of conditions and the following disclaimer in the
 documentation and/or other materials provided with the distribution.

 THIS SOFTWARE IS PROVIDED ``AS IS'' AND ANY EXPRESS OR IMPLIED WARRANTIES,
 INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY
 AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY,
 OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 POSSIBILITY OF SUCH DAMAGE.
 */

require("guiconfig.inc");

// The page title for non-admins
$pgtitle = array("System", "User password");

// Page title for main admin
$pgtitle = array("System", "User manager");

$id = $_GET['id'];
if (isset($_POST['id']))
$id = $_POST['id'];

if (!is_array($config['system']['accounts']['user'])) {
	$config['system']['accounts']['user'] = array();
}
admin_users_sort();
$a_user = &$config['system']['accounts']['user'];

if ($_GET['act'] == "del") {
	if ($a_user[$_GET['id']]) {
		$userdeleted = $a_user[$_GET['id']]['name'];
		unset($a_user[$_GET['id']]);
		write_config();
		$retval = system_password_configure();
		$savemsg = get_std_save_message($retval);
		$savemsg = "User ".$userdeleted." successfully deleted<br>";
	}
}

if ($_POST) {

	unset($input_errors);
	$pconfig = $_POST;

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

	if($_POST['username']==$config['system']['username']) {
		$input_errors[] = "username can not match the administrator username!";
	}

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
		$retval = system_password_configure();
		$savemsg = get_std_save_message($retval);
			
		header("Location: system_usermanager.php");
	}
}
?>
<p class="pgtitle"><?=join(": ", $pgtitle);?></p>
<script
	type="text/javascript" src="js/contentload.js"></script>
<?php if ($input_errors) print_input_errors($input_errors); ?>
<?php if ($savemsg) print_info_box($savemsg); ?>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
	<tr>
		<td class="tabcont"><?php
		if($_GET['act']=="new" || $_GET['act']=="edit" || $input_errors){
			if($_GET['act']=="edit"){
				if (isset($id) && $a_user[$id]) {
					$pconfig['username'] = $a_user[$id]['name'];
					$pconfig['fullname'] = $a_user[$id]['fullname'];
				}
			}
			?> <script type="text/javascript">

// wait for the DOM to be loaded
$(document).ready(function() {
    $('div fieldset div').addClass('ui-widget ui-widget-content ui-corner-content');
    $("#submitbutton").click(function () {
        displayProcessingDiv();
        var QueryString = $("#iform").serialize();
        $.post("forms/system_form_submit.php", QueryString, function(output) {
            $("#save_config").html(output);
            if(output.match(/SUBMITSUCCESS/))
                setTimeout(function(){ $('#save_config').dialog('close'); }, 1000);
        });
    return false;
    });
});
</script>

		<form action="form_submit.php" method="post" name="iform" id="iform">
		<input name="formname" type="hidden" value="system_users">
		<table width="100%" border="0" cellpadding="6" cellspacing="0">
			<tr>
				<td width="22%" valign="top" class="vncellreq">Username</td>
				<td width="78%" class="vtable"><input name="username" type="text"
					class="formfld" id="username" size="20"
					value="<?=htmlspecialchars($pconfig['username']);?>"></td>
			</tr>
			<tr>
				<td width="22%" valign="top" class="vncellreq">Password</td>
				<td width="78%" class="vtable"><input name="password"
					type="password" class="formfld" id="password" size="20" value=""> <br>
				<input name="password2" type="password" class="formfld"
					id="password2" size="20" value=""> &nbsp;(confirmation)</td>
			</tr>
			<tr>
				<td width="22%" valign="top" class="vncell">Full name</td>
				<td width="78%" class="vtable"><input name="fullname" type="text"
					class="formfld" id="fullname" size="20"
					value="<?=htmlspecialchars($pconfig['fullname']);?>"> <br>
				User's full name, for your own information only</td>
			</tr>
			<tr>
				<td width="22%" valign="top">&nbsp;</td>
				<td width="78%"><input name="save" type="submit" class="formbtn"
					value="Save"> <?php if (isset($id) && $a_user[$id]): ?> <input
					name="id" type="hidden" value="<?=$id;?>"> <?php endif; ?></td>
			</tr>
		</table>
		</form>
		<?php
		} else {
			?>
		<table width="100%" border="0" cellpadding="0" cellspacing="0">
			<tr>
				<td width="35%" class="listhdrr">Username</td>
				<td width="20%" class="listhdrr">Full name</td>
				<td width="10%" class="list"></td>
			</tr>
			<?php $i = 0; foreach($a_user as $userent): ?>
			<tr>
				<td class="listlr"><?=htmlspecialchars($userent['name']); ?>&nbsp;</td>
				<td class="listr"><?=htmlspecialchars($userent['fullname']);?>&nbsp;
				</td>
				<td valign="middle" nowrap class="list"><a
					href="javascript:loadContent('system_usermanager.php?act=edit&id=<?=$i; ?>');"><img
					src="images/e.gif" title="edit user" width="17" height="17"
					border="0"></a> &nbsp;<a
					href="javascript:loadContent('system_usermanager.php?act=del&id=<?=$i; ?>');"
					onclick="return confirm('Do you really want to delete this User?')"><img
					src="images/x.gif" title="delete user" width="17" height="17"
					border="0"></a></td>
			</tr>
			<?php $i++; endforeach; ?>
			<tr>
				<td class="list" colspan="3"></td>
				<td class="list"><a
					href="javascript:loadContent('system_usermanager.php?act=new');"><img
					src="images/plus.gif" title="add user" width="17" height="17"
					border="0"></a></td>
			</tr>
			<tr>
				<td colspan="3">Additional webGui users can be added here.</td>
			</tr>
		</table>
		<?php } ?></td>
	</tr>
</table>
</form>