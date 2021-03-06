#!/usr/bin/php
<?php
/*
 * Copyright (C) 2005		Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2013	Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2013		Juanjo Menent <jmenent@2byte.es>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *      \file       scripts/contracts/email_expire_services_to_customers.php
 *      \ingroup    facture
 *      \brief      Script to send a mail to customers with services to expire
 */

$sapi_type = php_sapi_name();
$script_file = basename(__FILE__);
$path=dirname(__FILE__).'/';

// Test si mode batch
$sapi_type = php_sapi_name();
if (substr($sapi_type, 0, 3) == 'cgi') {
    echo "Error: You are using PHP for CGI. To execute ".$script_file." from command line, you must use PHP for CLI mode.\n";
	exit(-1);
}

if (! isset($argv[1]) || ! $argv[1] || ! in_array($argv[1],array('test','confirm')))
{
	print "Usage: $script_file [test|confirm] [delay]\n";
	print "\n";
	print "Send an email to customers to remind all all contracts services to expire.\n";
	print "If you choose 'test' mode, no emails are sent.\n";
	print "If you add a delay (nb of days), only services with expired date < today + delay are included.\n";
	exit(-1);
}
$mode=$argv[1];


require($path."../../htdocs/master.inc.php");
require_once (DOL_DOCUMENT_ROOT."/core/class/CMailFile.class.php");

$langs->load('main');
$langs->load('contracts');


// Global variables
$version=DOL_VERSION;
$error=0;


/*
 * Main
 */

@set_time_limit(0);
print "***** ".$script_file." (".$version.") pid=".getmypid()." *****\n";

$now=dol_now('tzserver');
$duration_value=isset($argv[2])?$argv[2]:'none';

print $script_file." launched with mode ".$mode.(is_numeric($duration_value)?" delay=".$duration_value:"")."\n";

if ($mode != 'confirm') $conf->global->MAIN_DISABLE_ALL_MAILS=1;

$sql  = "SELECT DISTINCT s.nom as name, c.ref, cd.date_fin_validite, cd.total_ttc, p.label label, s.email, s.default_lang";
$sql .= " FROM ".MAIN_DB_PREFIX."societe AS s";
$sql .= ", ".MAIN_DB_PREFIX."contrat AS c";
$sql .= ", ".MAIN_DB_PREFIX."contratdet AS cd";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."product AS p ON p.rowid = cd.fk_product";
$sql .= " WHERE s.rowid = c.fk_soc AND c.rowid = cd.fk_contrat AND c.statut > 0 AND cd.statut<5";

if (is_numeric($duration_value)) $sql .= " AND cd.date_fin_validite < '".$db->idate(dol_time_plus_duree($now, $duration_value, "d"))."'";

$sql .= " ORDER BY cd.date_fin_validite ASC, s.rowid ASC";

print $sql;
$resql=$db->query($sql);
if ($resql)
{
    $num = $db->num_rows($resql);
    $i = 0;
    $oldemail = 'none'; $oldlang='';
    $total = 0; $foundtoprocess = 0;
	print "We found ".$num." couples (services to expire - customer) qualified\n";
    dol_syslog("We found ".$num." couples (services to expire - customer) qualified");
	$message='';

    if ($num)
    {
        while ($i < $num)
        {
            $obj = $db->fetch_object($resql);

            if (($obj->email <> $oldemail) || $oldemail == 'none')
            {
                // Break onto sales representative (new email or uid)
                if (dol_strlen($oldemail) && $oldemail != 'none')
                {
                   	envoi_mail($mode,$oldemail,$message,$total,$oldlang,$oldcustomer,$duration_value);
                }
                else
				{
                	if ($oldemail != 'none') print "- No email sent for ".$oldcustomer.", total: ".$total."\n";
                }
                $oldemail = $obj->email;
                $oldlang = $obj->lang;
                $oldcustomer=$obj->name;
                $message = '';
                $total = 0;
                $foundtoprocess = 0;
                $customer=$obj->name;
                if (empty($obj->email)) print "Warning: Customer ".$customer." has no email. Notice disabled.\n";
            }

            if (dol_strlen($oldemail))
            {
            	$message .= $langs->trans("Contract")." ".$obj->ref.": ".$langs->trans("Service")." ".$obj->label." (".price($obj->total_ttc)."), ".$langs->trans("DateEndPlannedShort")." ".dol_print_date($db->jdate($obj->date_fin_validite),'day')."\n\n";
            	dol_syslog("email_expire_services_to_customers.php: ".$obj->email);
            	$foundtoprocess++;
            }
            print "Service to expire ".$obj->ref.", label ".$obj->label.", due date ".dol_print_date($db->jdate($obj->date_fin_validite),'day')." (linked to company ".$obj->nom.", sale representative ".dolGetFirstLastname($obj->firstname, $obj->lastname).", email ".$obj->email."): ";
            if (dol_strlen($obj->email)) print "qualified.";
            else print "disqualified (no email).";
			print "\n";

            $total += $obj->total_ttc;

            $i++;
        }

        // Si il reste des envois en buffer
        if ($foundtoprocess)
        {
            if (dol_strlen($oldemail) && $oldemail != 'none')	// Break onto email (new email)
            {
       			envoi_mail($mode,$oldemail,$message,$total,$oldlang,$oldcustomer,$duration_value);
            }
            else
			{
            	if ($oldemail != 'none') print "- No email sent for ".$oldcustomer.", total: ".$total."\n";
            }
        }
    }
    else
    {
        print "No unpaid invoices found\n";
    }

    exit(0);
}
else
{
    dol_print_error($db);
    dol_syslog("email_expire_services_to_customers.php: Error");

    exit(-1);
}


/**
 * 	Send email
 *
 * 	@param	string	$mode			Mode (test | confirm)
 *  @param	string	$oldemail		Old email
 * 	@param	string	$message		Message to send
 * 	@param	string	$total			Total amount of unpayed invoices
 *  @param	string	$userlang		Code lang to use for email output.
 *  @param	string	$oldcustomer	Old customer
 *  @param  int		$duration_value	duration value
 * 	@return	int						<0 if KO, >0 if OK
 */
function envoi_mail($mode,$oldemail,$message,$total,$userlang,$oldcustomer,$duration_value)
{
    global $conf,$langs;

    if (getenv('DOL_FORCE_EMAIL_TO')) $oldemail=getenv('DOL_FORCE_EMAIL_TO');

    $newlangs=new Translate('',$conf);
    $newlangs->setDefaultLang(empty($userlang)?(empty($conf->global->MAIN_LANG_DEFAULT)?'auto':$conf->global->MAIN_LANG_DEFAULT):$userlang);
    $newlangs->load("main");
    $newlangs->load("contracts");

    if ($duration_value)
    	$title=$newlangs->transnoentities("ListOfServicesToExpireWithDuration",$duration_value);
    else
    	$title= $newlangs->transnoentities("ListOfServicesToExpire");

    $subject = "[".(empty($conf->global->MAIN_APPLICATION_TITLE)?'Dolibarr':$conf->global->MAIN_APPLICATION_TITLE)."] ".$title;
    $sendto = $oldemail;
    $from = $conf->global->MAIN_MAIL_EMAIL_FROM;
    $errorsto = $conf->global->MAIN_MAIL_ERRORS_TO;
	$msgishtml = -1;

    print "- Send email for ".$oldcustomer."(".$oldemail."), total: ".$total."\n";
    dol_syslog("email_expire_services_to_customers.php: send mail to ".$oldemail);

    $usehtml=0;
    if (dol_textishtml($conf->global->SCRIPT_EMAIL_EXPIRE_SERVICES_CUSTOMERS_FOOTER)) $usehtml+=1;
    if (dol_textishtml($conf->global->SCRIPT_EMAIL_EXPIRE_SERVICES_CUSTOMERS_HEADER)) $usehtml+=1;

    $allmessage='';
    if (! empty($conf->global->SCRIPT_EMAIL_EXPIRE_SERVICES_CUSTOMERS_HEADER))
    {
    	$allmessage.=$conf->global->SCRIPT_EMAIL_EXPIRE_SERVICES_CUSTOMERS_HEADER;
    }
    else
    {
    	$allmessage.= "Dear customer".($usehtml?"<br>\n":"\n").($usehtml?"<br>\n":"\n");
    	$allmessage.= "Please, find a summary of the services contracted by you that are about to expire.".($usehtml?"<br>\n":"\n").($usehtml?"<br>\n":"\n");
    	$allmessage.= "Note: This list contains only services to expire.".($usehtml?"<br>\n":"\n").($usehtml?"<br>\n":"\n");
    }
    $allmessage.= $message.($usehtml?"<br>\n":"\n");
    $allmessage.= $langs->trans("Total")." = ".price($total).($usehtml?"<br>\n":"\n");
    if (! empty($conf->global->SCRIPT_EMAIL_EXPIRE_SERVICES_CUSTOMERS_FOOTER))
    {
    	$allmessage.=$conf->global->SCRIPT_EMAIL_EXPIRE_SERVICES_CUSTOMERS_FOOTER;
    	if (dol_textishtml($conf->global->SCRIPT_EMAIL_EXPIRE_SERVICES_CUSTOMERS_FOOTER)) $usehtml+=1;
    }

    $mail = new CMailFile(
        $subject,
        $sendto,
        $from,
        $allmessage,
        array(),
        array(),
        array(),
        '',
        '',
        0,
        $msgishtml
    );

    $mail->errors_to = $errorsto;

    // Send or not email
    if ($mode == 'confirm')
    {
    	$result=$mail->sendfile();
    	if (! $result)
    	{
    		print "Error sending email ".$mail->error."\n";
    		dol_syslog("Error sending email ".$mail->error."\n");
    	}
    }
    else
    {
    	print "No email sent (test mode)\n";
    	dol_syslog("No email sent (test mode)");
    	$mail->dump_mail();
    	$result=1;
    }

    if ($result)
    {
        return 1;
    }
    else
    {
        return -1;
    }
}

?>