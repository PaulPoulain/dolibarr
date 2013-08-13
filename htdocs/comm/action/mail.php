<?php


require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/agenda.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT.'/comm/action/class/cactioncomm.class.php';
require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php';
require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/propal.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/order.lib.php';
require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/invoice.lib.php';





$name="username";
$imap = imap_open("{mail.exemple.com:143}INBOX","username","password");

$message_count = imap_num_msg($imap);

$object = new Propal($db);
$cactioncomm = new CActionComm($db);
$actioncomm = new ActionComm($db);
$contact = new Contact($db);
$objp = new Commande($db);
$obj = new Facture($db);
$usertodo=new User($db);
$userdone=new User($db);
$societe = new Societe($db);

for($i=1;$i<=$message_count;$i++)
{
	$header = imap_header($imap,$i);
	$sujet = $header->Subject;
	
	if ( preg_match_all("(PR)([0-9][0-9][0-9][0-9])-([0-9][0-9][0-9][0-9])",$sujet,$reponse,PREG_PATTERN_ORDER))
	{
		$origin ='propal';

		$ret = $object->fetch($rowid='',$reponse[0]);
		
		if ($ret > 0)
		{
			$resql=$cactioncomm->fetch('AC_PROP');
			$societe->fetch($object->fk_soc);
			$actioncomm->societe = $societe;

		}
		
		//if ($ret < 0) dol_print_error('',$object->error);
		
	}
	if ( preg_match_all("(CO)([0-9][0-9][0-9][0-9])-([0-9][0-9][0-9][0-9])",$sujet,$reponse,PREG_PATTERN_ORDER))
	{
		$origin = 'commande';
		
		$ret = $objp->fetch($id='', $reponse[0], $ref_ext='', $ref_int='');
		
		if ($ret > 0) $resql=$cactioncomm->fetch('AC_COM');
		$societe->fetch($objp->fk_soc);
		$actioncomm->societe = $societe;
		
		//if ($ret < 0) dol_print_error('',$objp->error);
	}
	if ( preg_match_all("(FA)([0-9][0-9][0-9][0-9])-([0-9][0-9][0-9][0-9])",$sujet,$reponse,PREG_PATTERN_ORDER))
	{
		$origin = 'facture';
		
		$ret = $obj->fetch($rowid='', $reponse[0], $ref_ext='', $ref_int='');
		
		if ($ret > 0) $resql=$cactioncomm->fetch('AC_FAC');
		$societe->fetch($obj->fk_soc);
		$actioncomm->societe = $societe;
		
		//if ($ret < 0) dol_print_error('',$obj->error);
	}
		// Initialisation objet actioncomm
	$actioncomm->type_id = $cactioncomm->id;
	$actioncomm->type_code = $cactioncomm->code;
	$actioncomm->priority =0;
	$actioncomm->fulldayevent =0;
	$actioncomm->location = GETPOST("location");
	$actioncomm->transparency = 0;
	
	$actioncomm->label = $langs->transnoentitiesnoconv("Action".$actioncomm->type_code)."\n";
			
	
	$actioncomm->fk_project = 0;
	$actioncomm->datep = $header->udate;
	$actioncomm->datef = $header->udate;
	$actioncomm->percentage = $percentage;
	$actioncomm->duree=((GETPOST('dureehour') * 60) + GETPOST('dureemin')) * 60;

	if ($_POST["affectedto"] > 0)
	{
		$usertodo->fetch($_POST["affectedto"]);
	}
	$actioncomm->usertodo = $usertodo;
	
	
	$userdone->fetch($name);
	$actioncomm->userdone = $userdone;

	$actioncomm->note = $sujet;
	$db->begin();

	// On cree l'action
	$idaction=$actioncomm->add($name);

}
		
		
	





















?>
