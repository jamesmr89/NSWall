#!/bin/php
<?php
/*
    system_certmanager.php

    Copyright (C) 2008 Shrew Soft Inc.
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

##|+PRIV
##|*IDENT=page-system-certmanager
##|*NAME=System: Certificate Manager
##|*DESCR=Allow access to the 'System: Certificate Manager' page.
##|*MATCH=system_certmanager.php*
##|-PRIV

require("guiconfig.inc");

$cert_methods = array(
    "existing" => "Import an existing Certificate",
    "internal" => "Create an internal Certificate",
    "external" => "Create a Certificate Signing Request");

$cert_keylens = array( "512", "1024", "2048", "4096");

$pgtitle = array("System", "Certificate Manager");

$id = $_GET['id'];
if (isset($_POST['id']))
    $id = $_POST['id'];

if (!is_array($config['system']['certmgr']['ca']))
    $config['system']['certmgr']['ca'] = array();

system_ca_sort();

$a_ca = &$config['system']['certmgr']['ca'];

if (!is_array($config['system']['certmgr']['cert']))
    $config['system']['certmgr']['cert'] = array();

system_cert_sort();

$a_cert = &$config['system']['certmgr']['cert'];

$internal_ca_count = 0;
foreach ($a_ca as $ca)
    if ($ca['prv'])    
        $internal_ca_count++;

$act = $_GET['act'];
if ($_POST['act'])
    $act = $_POST['act'];

if ($act == "del") {

    if (!$a_cert[$id]) {
        header("system_certmanager.php");
        exit;
    }

    $name = $a_cert[$id]['name'];
    unset($a_cert[$id]);
    write_config();
    $savemsg = "Certificate"." {$name} ".
                "successfully deleted"."<br/>";
}

if ($act == "new") {
    $pconfig['method'] = $_GET['method'];
    $pconfig['keylen'] = "2048";
    $pconfig['lifetime'] = "365";
}

if ($act == "exp") {

    if (!$a_cert[$id]) {
        header("system_certmanager.php");
        exit;
    }

    $exp_name = urlencode("{$a_cert[$id]['name']}.crt");
    $exp_data = base64_decode($a_cert[$id]['crt']);
    $exp_size = strlen($exp_data);

    header("Content-Type: application/octet-stream");
    header("Content-Disposition: attachment; filename={$exp_name}");
    header("Content-Length: $exp_size");
    echo $exp_data;
    exit;
}

if ($act == "csr") {

    if (!$a_cert[$id]) {
        header("system_certmanager.php");
        exit;
    }

    $pconfig['name'] = $a_cert[$id]['name'];
    $pconfig['csr'] = base64_decode($a_cert[$id]['csr']);
}

if ($_POST) {

    if ($_POST['save'] == "Save") {

        unset($input_errors);
        $pconfig = $_POST;

        /* input validation */
        if ($pconfig['method'] == "existing") {
            $reqdfields = explode(" ",
                    "name cert key");
            $reqdfieldsn = explode(",",
                    "Desriptive name,Certificate data,Key data");
        }

        if ($pconfig['method'] == "internal") {
            $reqdfields = explode(" ",
                    "name caref keylen lifetime dn_country dn_state dn_city ".
                    "dn_organization dn_email dn_commonname");
            $reqdfieldsn = explode(",",
                    "Desriptive name,Certificate authority,Key length,Lifetime,".
                    "Distinguished name Country Code,".
                    "Distinguished name State or Province,".
                    "Distinguished name City,".
                    "Distinguished name Organization,".
                    "Distinguished name Email Address,".
                    "Distinguished name Common Name");
        }

        if ($pconfig['method'] == "external") {
            $reqdfields = explode(" ",
                    "name csr_keylen csr_dn_country csr_dn_state csr_dn_city ".
                    "csr_dn_organization csr_dn_email csr_dn_commonname");
            $reqdfieldsn = explode(",",
                    "Desriptive name,Key length,".
                    "Distinguished name Country Code,".
                    "Distinguished name State or Province,".
                    "Distinguished name City,".
                    "Distinguished name Organization,".
                    "Distinguished name Email Address,".
                    "Distinguished name Common Name");
        }

        do_input_validation($_POST, $reqdfields, $reqdfieldsn, &$input_errors);

        /* save modifications */
        if (!$input_errors) {

            $cert = array();
            $cert['refid'] = uniqid('');
            if (isset($id) && $a_cert[$id])
                $cert = $a_cert[$id];

            $cert['name'] = $pconfig['name'];

            if ($pconfig['method'] == "existing")
                cert_import(& $cert, $pconfig['cert'], $pconfig['key']);

            if ($pconfig['method'] == "internal") {
                $dn = array(
                    'countryName' => $pconfig['dn_country'],
                    'stateOrProvinceName' => $pconfig['dn_state'],
                    'localityName' => $pconfig['dn_city'],
                    'organizationName' => $pconfig['dn_organization'],
                    'emailAddress' => $pconfig['dn_email'],
                    'commonName' => $pconfig['dn_commonname']);

                cert_create(& $cert, $pconfig['caref'], $pconfig['keylen'],
                    $pconfig['lifetime'], $dn);
            }

            if ($pconfig['method'] == "external") {
                $dn = array(
                    'countryName' => $pconfig['csr_dn_country'],
                    'stateOrProvinceName' => $pconfig['csr_dn_state'],
                    'localityName' => $pconfig['csr_dn_city'],
                    'organizationName' => $pconfig['csr_dn_organization'],
                    'emailAddress' => $pconfig['csr_dn_email'],
                    'commonName' => $pconfig['csr_dn_commonname']);

                csr_generate(& $cert, $pconfig['csr_keylen'], $dn);
            }

            if (isset($id) && $a_cert[$id])
                $a_cert[$id] = $cert;
            else
                $a_cert[] = $cert;

            write_config();
	    push_config('certmgr');
//            pfSenseHeader("system_certmanager.php");
        }
    }

    if ($_POST['save'] == "Update") {
        unset($input_errors);
        $pconfig = $_POST;

        /* input validation */
        $reqdfields = explode(" ", "name cert");
        $reqdfieldsn = explode(",", "Desriptive name,Final Certificate data");

        do_input_validation($_POST, $reqdfields, $reqdfieldsn, &$input_errors);

        /* save modifications */
        if (!$input_errors) {

            $cert = $a_cert[$id];

            $cert['name'] = $pconfig['name'];

            csr_complete($cert, $pconfig['cert']);

            $a_cert[$id] = $cert;

            write_config();

            pfSenseHeader("system_certmanager.php");
        }
    }
}

?>

<body link="#000000" vlink="#000000" alink="#000000" onload="<?= $jsevents["body"]["onload"] ?>">
<?php include("fbegin.inc"); ?>
<script type="text/javascript">
<!--

function method_change() {

<?php
    if ($internal_ca_count)
        $submit_style = "";
    else
        $submit_style = "none";
?>

    method = document.iform.method.selectedIndex;

    switch (method) {
        case 0:
            document.getElementById("existing").style.display="";
            document.getElementById("internal").style.display="none";
            document.getElementById("external").style.display="none";
            document.getElementById("submit").style.display="";
            break;
        case 1:
            document.getElementById("existing").style.display="none";
            document.getElementById("internal").style.display="";
            document.getElementById("external").style.display="none";
            document.getElementById("submit").style.display="<?=$submit_style;?>";
            break;
        case 2:
            document.getElementById("existing").style.display="none";
            document.getElementById("internal").style.display="none";
            document.getElementById("external").style.display="";
            document.getElementById("submit").style.display="";
            break;
    }
}

<?php if ($internal_ca_count): ?>
function internalca_change() {

    index = document.iform.caref.selectedIndex;
    caref = document.iform.caref[index].value;

    switch (caref) {
<?php
        foreach ($a_ca as $ca):
            if (!$ca['prv'])
                continue;
            $subject = cert_get_subject_array($ca['crt']);
?>
        case "<?=$ca['refid'];?>":
            document.iform.dn_country.value = "<?=$subject[0]['v'];?>";
            document.iform.dn_state.value = "<?=$subject[1]['v'];?>";
            document.iform.dn_city.value = "<?=$subject[2]['v'];?>";
            document.iform.dn_organization.value = "<?=$subject[3]['v'];?>";
            break;
<?php    endforeach; ?>
    }
}
<?php endif; ?>

//-->
</script>
<?php
    if ($input_errors)
        print_input_errors($input_errors);
    if ($savemsg)
        print_info_box($savemsg);
?>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
    <tr>
        <td class="tabnavtbl">
        <ul id="tabnav">
	<?php
            $tabs = array();
            $tabs = array("CAs" => "system_camanager.php",
            		  "Certificates" => "system_certmanager.php");
            dynamic_tab_menu($tabs);
        ?>
 	</ul>
        </td>
    </tr>
    <tr>
        <td id="mainarea">
            <div class="tabcont">

                <?php if ($act == "new" || (($_POST['save'] == "Save") && $input_errors)): ?>

                <form action="system_certmanager.php" method="post" name="iform" id="iform">
                    <table width="100%" border="0" cellpadding="6" cellspacing="0">
                        <tr>
                            <td width="22%" valign="top" class="vncellreq"><?="Descriptive name";?></td>
                            <td width="78%" class="vtable">
                                <input name="name" type="text" class="formfld unknown" id="name" size="20" value="<?=htmlspecialchars($pconfig['name']);?>"/>
                            </td>
                        </tr>
                        <?php if (!isset($id)): ?>
                        <tr>
                            <td width="22%" valign="top" class="vncellreq"><?="Method";?></td>
                            <td width="78%" class="vtable">
                                <select name='method' id='method' class="formselect" onchange='method_change()'>
                                <?php
                                    foreach($cert_methods as $method => $desc):
                                    $selected = "";
                                    if ($pconfig['method'] == $method)
                                        $selected = "selected";
                                ?>
                                    <option value="<?=$method;?>"<?=$selected;?>><?=$desc;?></option>
                                <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </table>

                    <table width="100%" border="0" cellpadding="6" cellspacing="0" id="existing">
                        <tr>
                            <td colspan="2" class="list" height="12"></td>
                        </tr>
                        <tr>
                            <td colspan="2" valign="top" class="listtopic">Existing Certificate</td>
                        </tr>

                        <tr>
                            <td width="22%" valign="top" class="vncellreq">Certificate data</td>
                            <td width="78%" class="vtable">
                                <textarea name="cert" id="cert" cols="65" rows="7" class="formfld_cert"><?=$pconfig['cert'];?></textarea>
                                <br>
                                Paste a certificate in X.509 PEM format here.</td>
                            </td>
                        </tr>
                        <tr>
                            <td width="22%" valign="top" class="vncellreq">Private key data</td>
                            <td width="78%" class="vtable">
                                <textarea name="key" id="key" cols="65" rows="7" class="formfld_cert"><?=$pconfig['key'];?></textarea>
                                <br>
                                Paste a private key in X.509 PEM format here.</td>
                            </td>
                        </tr>
                    </table>

                    <table width="100%" border="0" cellpadding="6" cellspacing="0" id="internal">
                        <tr>
                            <td colspan="2" class="list" height="12"></td>
                        </tr>
                        <tr>
                            <td colspan="2" valign="top" class="listtopic">Internal Certificate</td>
                        </tr>

                        <?php if (!$internal_ca_count): ?>

                        <tr>
                            <td colspan="2" align="center" class="vtable">
                                No internal Certificate Authorities have been defined. You must
                                <a href="system_camanager.php?act=new&method=internal">create</a>
                                an internal CA before creating an internal certificate.
                            </td>
                        </tr>

                        <?php else: ?>

                        <tr>
                            <td width="22%" valign="top" class="vncellreq"><?="Certificate authority";?></td>
                            <td width="78%" class="vtable">
                                <select name='caref' id='caref' class="formselect" onChange='internalca_change()'>
                                <?php
                                    foreach( $a_ca as $ca):
                                    if (!$ca['prv'])
                                        continue;
                                    $selected = "";
                                    if ($pconfig['caref'] == $ca['refid'])
                                        $selected = "selected";
                                ?>
                                    <option value="<?=$ca['refid'];?>"<?=$selected;?>><?=$ca['name'];?></option>
                                <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td width="22%" valign="top" class="vncellreq"><?="Key length";?></td>
                            <td width="78%" class="vtable">
                                <select name='keylen' class="formselect">
                                <?php
                                    foreach( $cert_keylens as $len):
                                    $selected = "";
                                    if ($pconfig['keylen'] == $len)
                                        $selected = "selected";
                                ?>
                                    <option value="<?=$len;?>"<?=$selected;?>><?=$len;?></option>
                                <?php endforeach; ?>
                                </select>
                                bits
                            </td>
                        </tr>
                        <tr>
                            <td width="22%" valign="top" class="vncellreq"><?="Lifetime";?></td>
                            <td width="78%" class="vtable">
                                <input name="lifetime" type="text" class="formfld unknown" id="lifetime" size="5" value="<?=htmlspecialchars($pconfig['lifetime']);?>"/>
                                days
                            </td>
                        </tr>
                        <tr>
                            <td width="22%" valign="top" class="vncellreq"><?="Distinguished name";?></td>
                            <td width="78%" class="vtable">
                                <table border="0" cellspacing="0" cellpadding="2">
                                    <tr>
                                        <td align="right">Country Code : &nbsp;</td>
                                        <td align="left">
                                            <input name="dn_country" type="text" class="formfld unknown" size="2" value="<?=htmlspecialchars($pconfig['dn_country']);?>" readonly/>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td align="right">State or Province : &nbsp;</td>
                                        <td align="left">
                                            <input name="dn_state" type="text" class="formfld unknown" size="40" value="<?=htmlspecialchars($pconfig['dn_state']);?>" readonly/>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td align="right">City : &nbsp;</td>
                                        <td align="left">
                                            <input name="dn_city" type="text" class="formfld unknown" size="40" value="<?=htmlspecialchars($pconfig['dn_city']);?>" readonly/>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td align="right">Organization : &nbsp;</td>
                                        <td align="left">
                                            <input name="dn_organization" type="text" class="formfld unknown" size="40" value="<?=htmlspecialchars($pconfig['dn_organization']);?>" readonly/>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td align="right">Email Address : &nbsp;</td>
                                        <td align="left">
                                            <input name="dn_email" type="text" class="formfld unknown" size="25" value="<?=htmlspecialchars($pconfig['dn_email']);?>"/>
                                            &nbsp;
                                            <em>ex:</em>
                                            &nbsp;
                                            webadmin@mycompany.com
                                        </td>
                                    </tr>
                                    <tr>
                                        <td align="right">Common Name : &nbsp;</td>
                                        <td align="left">
                                            <input name="dn_commonname" type="text" class="formfld unknown" size="25" value="<?=htmlspecialchars($pconfig['dn_commonname']);?>"/>
                                            &nbsp;
                                            <em>ex:</em>
                                            &nbsp;
                                            www.pfsense.org
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>

                    <?php endif; ?>

                    </table>

                    <table width="100%" border="0" cellpadding="6" cellspacing="0" id="external">
                        <tr>
                            <td colspan="2" class="list" height="12"></td>
                        </tr>
                        <tr>
                            <td colspan="2" valign="top" class="listtopic">External Signing Request</td>
                        </tr>
                        <tr>
                            <td width="22%" valign="top" class="vncellreq"><?="Key length";?></td>
                            <td width="78%" class="vtable">
                                <select name='csr_keylen' class="formselect">
                                <?php
                                    foreach( $cert_keylens as $len):
                                    $selected = "";
                                    if ($pconfig['keylen'] == $len)
                                        $selected = "selected";
                                ?>
                                    <option value="<?=$len;?>"<?=$selected;?>><?=$len;?></option>
                                <?php endforeach; ?>
                                </select>
                                bits
                            </td>
                        </tr>
                        <tr>
                            <td width="22%" valign="top" class="vncellreq"><?="Distinguished name";?></td>
                            <td width="78%" class="vtable">
                                <table border="0" cellspacing="0" cellpadding="2">
                                    <tr>
                                        <td align="right">Country Code : &nbsp;</td>
                                        <td align="left">
                                            <input name="csr_dn_country" type="text" class="formfld unknown" size="2" value="<?=htmlspecialchars($pconfig['csr_dn_country']);?>" />
                                            &nbsp;
                                            <em>ex:</em>
                                            &nbsp;
                                            US
                                            &nbsp;
                                            <em>( two letters )</em>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td align="right">State or Province : &nbsp;</td>
                                        <td align="left">
                                            <input name="csr_dn_state" type="text" class="formfld unknown" size="40" value="<?=htmlspecialchars($pconfig['csr_dn_state']);?>" />
                                            &nbsp;
                                            <em>ex:</em>
                                            &nbsp;
                                            Texas
                                        </td>
                                    </tr>
                                    <tr>
                                        <td align="right">City : &nbsp;</td>
                                        <td align="left">
                                            <input name="csr_dn_city" type="text" class="formfld unknown" size="40" value="<?=htmlspecialchars($pconfig['csr_dn_city']);?>" />
                                            &nbsp;
                                            <em>ex:</em>
                                            &nbsp;
                                            Austin
                                        </td>
                                    </tr>
                                    <tr>
                                        <td align="right">Organization : &nbsp;</td>
                                        <td align="left">
                                            <input name="csr_dn_organization" type="text" class="formfld unknown" size="40" value="<?=htmlspecialchars($pconfig['csr_dn_organization']);?>" />
                                            &nbsp;
                                            <em>ex:</em>
                                            &nbsp;
                                            My Company Inc.
                                        </td>
                                    </tr>
                                    <tr>
                                        <td align="right">Email Address : &nbsp;</td>
                                        <td align="left">
                                            <input name="csr_dn_email" type="text" class="formfld unknown" size="25" value="<?=htmlspecialchars($pconfig['csr_dn_email']);?>"/>
                                            &nbsp;
                                            <em>ex:</em>
                                            &nbsp;
                                            webadmin@mycompany.com
                                        </td>
                                    </tr>
                                    <tr>
                                        <td align="right">Common Name : &nbsp;</td>
                                        <td align="left">
                                            <input name="csr_dn_commonname" type="text" class="formfld unknown" size="25" value="<?=htmlspecialchars($pconfig['csr_dn_commonname']);?>"/>
                                            &nbsp;
                                            <em>ex:</em>
                                            &nbsp;
                                            www.pfsense.org
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>

                    <table width="100%" border="0" cellpadding="6" cellspacing="0">
                        <tr>
                            <td width="22%" valign="top">&nbsp;</td>
                            <td width="78%">
                                <input id="submit" name="save" type="submit" class="formbtn" value="Save" />
                                <?php if (isset($id) && $a_cert[$id]): ?>
                                <input name="id" type="hidden" value="<?=$id;?>" />
                                <?php endif;?>
                            </td>
                        </tr>
                    </table>
                </form>

                <?php elseif ($act == "csr" || (($_POST['save'] == "Update") && $input_errors)):?>

                <form action="system_certmanager.php" method="post" name="iform" id="iform">
                    <table width="100%" border="0" cellpadding="6" cellspacing="0">
                        <tr>
                            <td width="22%" valign="top" class="vncellreq"><?="Descriptive name";?></td>
                            <td width="78%" class="vtable">
                                <input name="name" type="text" class="formfld unknown" id="name" size="20" value="<?=htmlspecialchars($pconfig['name']);?>"/>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2" class="list" height="12"></td>
                        </tr>
                        <tr>
                            <td colspan="2" valign="top" class="listtopic">Complete Signing Request</td>
                        </tr>

                        <tr>
                            <td width="22%" valign="top" class="vncellreq">Signing Request data</td>
                            <td width="78%" class="vtable">
                                <textarea name="csr" id="csr" cols="65" rows="7" class="formfld_cert" readonly><?=$pconfig['csr'];?></textarea>
                                <br>
                                Copy the certificate signing data from here and forward it to your certificate authority for singing.</td>
                            </td>
                        </tr>
                        <tr>
                            <td width="22%" valign="top" class="vncellreq">Final Certificate data</td>
                            <td width="78%" class="vtable">
                                <textarea name="cert" id="cert" cols="65" rows="7" class="formfld_cert"><?=$pconfig['cert'];?></textarea>
                                <br>
                                Paste the certificate received from your cerificate authority here.</td>
                            </td>
                        </tr>
                        <tr>
                            <td width="22%" valign="top">&nbsp;</td>
                            <td width="78%">
                                <input id="submit" name="save" type="submit" class="formbtn" value="Update" />
                                <?php if (isset($id) && $a_cert[$id]): ?>
                                <input name="id" type="hidden" value="<?=$id;?>" />
                                <input name="act" type="hidden" value="csr" />
                                <?php endif;?>
                            </td>
                        </tr>
                    </table>
                </form>

                <?php else:?>

                <table width="100%" border="0" cellpadding="0" cellspacing="0">
                    <tr>
                        <td width="20%" class="listhdrr">Name</td>
                        <td width="20%" class="listhdrr">CA</td>
                        <td width="40%" class="listhdrr">Distinguished Name</td>
                        <td width="10%" class="list"></td>
                    </tr>
                    <?php
                        $i = 0;
                        foreach($a_cert as $cert):
                            $name = htmlspecialchars($cert['name']);

                            if ($cert['crt']) {
                                $subj = htmlspecialchars(cert_get_subject($cert['crt']));
                                $caname = "<em>external</em>";
                            }

                            $ca = lookup_ca($cert['caref']);
                            if ($ca)
                                $caname = $ca['name'];

                            if($cert['prv'])
                                $certimg = "cert.png";
                            else
                                $certimg = "cert.png";
                    ?>
                    <tr>
                        <td class="listlr">
                            <table border="0" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="left" valign="center">
                                        <img src="<?=$certimg;?>" alt="CA" title="CA" border="0" height="16" width="16" />
                                    </td>
                                    <td align="left" valign="middle">
                                        <?=$name;?>
                                    </td>
                                </tr>
                            </table>
                        </td>
                        <td class="listr"><?=$caname;?>&nbsp;</td>
                        <td class="listr"><?=$subj;?>&nbsp;</td>
                        <td valign="middle" nowrap class="list">
                            <a href="system_certmanager.php?act=exp&id=<?=$i;?>")">
                                <img src="images/down.gif" title="export cert" alt="export ca" width="17" height="17" border="0" />
                            </a>
                            <a href="system_certmanager.php?act=del&id=<?=$i;?>" onclick="return confirm('<?="Do you really want to delete this Certificate?";?>')">
                                <img src="images/x.gif" title="delete cert" alt="delete cert" width="17" height="17" border="0" />
                            </a>
                            <?php    if ($cert['csr']): ?>
                            &nbsp;
                                <a href="system_certmanager.php?act=csr&id=<?=$i;?>">
                                <img src="images/e.gif" title="update csr" alt="update csr" width="17" height="17" border="0" />
                            </a>
                            <?php    endif; ?>
                        </td>
                    </tr>
                    <?php
                            $i++;
                        endforeach;
                    ?>
                    <tr>
                        <td class="list" colspan="3"></td>
                        <td class="list">
                            <a href="system_certmanager.php?act=new">
                                <img src="images/plus.gif" title="add or import ca" alt="add ca" width="17" height="17" border="0" />
                            </a>
                        </td>
                    </tr>
                </table>

                <?php endif; ?>

            </div>
        </td>
    </tr>
</table>
<?php include("fend.inc");?>
<script type="text/javascript">
<!--

method_change();
internalca_change();

//-->
</script>

</body>
