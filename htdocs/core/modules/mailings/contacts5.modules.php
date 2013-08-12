<?php
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@capnetworks.com>
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
 * or see http://www.gnu.org/
 */

/**
 *	\file       htdocs/core/modules/mailings/contacts1.modules.php
 *	\ingroup    mailing
 *	\brief      File of class to offer a selector of emailing targets with Rule 'Poire'.
 */

include_once DOL_DOCUMENT_ROOT.'/core/modules/mailings/modules_mailings.php';


/**
 *	\class      mailing_contacts1
 *	\brief      Class to offer a selector of emailing targets with Rule 'Poire'.
 */
class mailing_contacts5 extends MailingTargets
{
	var $name='Contact';                     // Identifiant du module mailing
	// This label is used if no translation is found for key MailingModuleDescXXX where XXX=name is found
	var $desc='Contacts des tiers (poste,fonction,catégorie de tiers,catégories de contact...)';
	var $require_module=array("societe");               // Module mailing actif si modules require_module actifs
	var $require_admin=0;                               // Module mailing actif pour user admin ou non
	var $picto='contact';

	var $db;


	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	function __construct($db)
	{
		$this->db=$db;
	}


    /**
	 *	On the main mailing area, there is a box with statistics.
	 *	If you want to add a line in this report you must provide an
	 *	array of SQL request that returns two field:
	 *	One called "label", One called "nb".
	 *
	 *	@return		array		Array with SQL requests
	 */
	function getSqlArrayForStats()
	{
		global $conf, $langs;

		$langs->load("commercial");

		$statssql=array();
		$statssql[0] = "SELECT '".$langs->trans("NbOfCompaniesContacts")."' as label,";
		$statssql[0].= " count(distinct(c.email)) as nb";
		$statssql[0].= " FROM ".MAIN_DB_PREFIX."socpeople as c";
		$statssql[0].= " WHERE c.entity IN (".getEntity('societe', 1).")";
		$statssql[0].= " AND c.email != ''";      // Note that null != '' is false
		$statssql[0].= " AND c.no_email = 0";

		return $statssql;
	}


	/**
	 *	Return here number of distinct emails returned by your selector.
	 *	For example if this selector is used to extract 500 different
	 *	emails from a text file, this function must return 500.
	 *
	 *  @param	string	$sql		Requete sql de comptage
	 *	@return		int
	 */
	function getNbOfRecipients1($sql='')
	{
		global $conf;

		$sql  = "SELECT count(distinct(c.email)) as nb";
		$sql.= " FROM ".MAIN_DB_PREFIX."socpeople as c";
    	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = c.fk_soc";
		$sql.= " WHERE c.entity IN (".getEntity('societe', 1).")";
		$sql.= " AND c.email != ''"; // Note that null != '' is false
		$sql.= " AND c.no_email = 0";

		return parent::getNbOfRecipients($sql);
	}
	
	  function getNbOfRecipients2($sql='')
    {
    	global $conf;

    	$sql = "SELECT count(distinct(sp.email)) as nb";
    	$sql.= " FROM ".MAIN_DB_PREFIX."socpeople as sp";
    	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = sp.fk_soc";
        $sql.= " WHERE sp.entity IN (".getEntity('societe', 1).")";
    	$sql.= " AND sp.email != ''";  // Note that null != '' is false
    	$sql.= " AND sp.no_email = 0";
    	
    	return parent::getNbOfRecipients($sql);
    }
    
    function getNbOfRecipients3($sql='')
    {
    	global $conf;

    	$sql = "SELECT count(distinct(c.email)) as nb";
        $sql.= " FROM ".MAIN_DB_PREFIX."socpeople as c";
    	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = c.fk_soc";
        $sql.= " WHERE c.entity IN (".getEntity('societe', 1).")";
        $sql.= " AND c.email != ''"; // Note that null != '' is false
        $sql.= " AND c.no_email = 0";
       
    	return parent::getNbOfRecipients($sql);
    }

	 function getNbOfRecipients4($sql='')
    {
    	global $conf;

    	
    	$sql = "SELECT count(distinct(c.email)) as nb";
        $sql.= " FROM ".MAIN_DB_PREFIX."socpeople as c";
        $sql.= " WHERE c.entity IN (".getEntity('societe', 1).")";
        $sql.= " AND c.email != ''"; // Note that null != '' is false
        $sql.= " AND c.no_email = 0";
      
    	return parent::getNbOfRecipients($sql);
    }
	


	/**
	 *   Affiche formulaire de filtre qui apparait dans page de selection des destinataires de mailings
	 *
	 *   @return     string      Retourne zone select
	 */
	function formFilter()
	{
		global $langs;
		$langs->load("companies");
		$langs->load("commercial");
		$langs->load("suppliers");

		
		$sql = "SELECT sp.poste, count(distinct(sp.email)) AS nb";
        $sql.= " FROM ".MAIN_DB_PREFIX."socpeople as sp";
        $sql.= " WHERE sp.entity IN (".getEntity('societe', 1).")";
        $sql.= " AND sp.email != ''";    // Note that null != '' is false
        $sql.= " AND sp.no_email = 0";
        $sql.= " AND (sp.poste IS NOT NULL AND sp.poste != '')";
        $sql.= " GROUP BY sp.poste";
        $sql.= " ORDER BY sp.poste";

        $resql = $this->db->query($sql);

        print '';
        print '<select name="filter2" class="flat">';
        print '<option value="all"></option>';
        if ($resql)
        {
            $num = $this->db->num_rows($resql);
            $i = 0;
            while ($i < $num)
            {
                $obj = $this->db->fetch_object($resql);
                print '<option value="'.$obj->poste.'">'.$obj->poste.' ('.$obj->nb.')</option>';
                $i++;
            }
        }
        print '</select>';
        
        print '<br><br>';
        
        $sql = "SELECT c.label, count(distinct(sp.email)) AS nb";
        $sql.= " FROM ".MAIN_DB_PREFIX."socpeople as sp,";
        $sql.= " ".MAIN_DB_PREFIX."categorie as c,";
        $sql.= " ".MAIN_DB_PREFIX."categorie_societe as cs";
        $sql.= " WHERE sp.email != ''";     // Note that null != '' is false
        $sql.= " AND sp.no_email = 0";
        $sql.= " AND sp.entity IN (".getEntity('societe', 1).")";
        $sql.= " AND cs.fk_categorie = c.rowid";
        $sql.= " AND cs.fk_societe = sp.fk_soc";
        $sql.= " GROUP BY c.label";
        $sql.= " ORDER BY c.label";

        $resql = $this->db->query($sql);

        print '';
        print '<select name="filter3" class="flat">';
        print '<option value="all"></option>';
        if ($resql)
        {
            $num = $this->db->num_rows($resql);
            $i = 0;
            while ($i < $num)
            {
                $obj = $this->db->fetch_object($resql);
				print '<option value="'.$obj->label.'">'.$obj->label.' ('.$obj->nb.')</option>';
                $i++;
            }
        }
        print '</select>';
        
        print '<br><br>';
         $sql = "SELECT c.label, count(distinct(sp.email)) AS nb";
        $sql.= " FROM ".MAIN_DB_PREFIX."socpeople as sp";
        $sql.= " INNER JOIN ".MAIN_DB_PREFIX."categorie_contact as cs ON cs.fk_socpeople=sp.rowid";
        $sql.= " INNER JOIN ".MAIN_DB_PREFIX."categorie as c ON cs.fk_categorie = c.rowid";
        $sql.= " WHERE sp.email != ''";     // Note that null != '' is false
        $sql.= " AND sp.no_email = 0";
        $sql.= " AND sp.entity IN (".getEntity('societe', 1).")";
        $sql.= " GROUP BY c.label";
        $sql.= " ORDER BY c.label";

        $resql = $this->db->query($sql);
        
        dol_syslog(get_class($this).':: formFilter sql='.$sql,LOG_DEBUG);
		if ($resql) {
	        print '';
	        print '<select name="filter4" class="flat">';
	        print '<option value="all"></option>';
	        if ($resql)
	        {
	            $num = $this->db->num_rows($resql);
	            $i = 0;
	            while ($i < $num)
	            {
	                $obj = $this->db->fetch_object($resql);
	                print '<option value="'.$obj->label.'">'.$obj->label.' ('.$obj->nb.')</option>';
	                $i++;
	            }
	        }
	        print '</select>';
		}
		else {
			$this->error=$this->db->lasterrno();
			dol_syslog("Error sql=".$sql." ".$this->error, LOG_ERR);
			return -1;
		}    
	}


	/**
	 *  Renvoie url lien vers fiche de la source du destinataire du mailing
	 *
     *  @param	int		$id		ID
	 *  @return string      	Url lien
	 */
	function url($id)
	{
		return '<a href="'.DOL_URL_ROOT.'/contact/fiche.php?id='.$id.'">'.img_object('',"contact").'</a>';
	}


	/**
	 *  Ajoute destinataires dans table des cibles
	 *
	 *  @param	int		$mailing_id    	Id of emailing
	 *  @param  array	$filtersarray   Requete sql de selection des destinataires
	 *  @return int           			<0 si erreur, nb ajout si ok
	 */
	 function add_to_target($mailing_id,$filtersarray=array())
    {
    	global $conf,$langs;

    	$target = array();

        // La requete doit retourner: id, email, fk_contact, name, firstname, other
        $sql = "SELECT sp.rowid as id, sp.email as email, sp.rowid as fk_contact,";
        $sql.= " sp.lastname, sp.firstname as firstname, sp.civilite,";
        $sql.= " s.nom as companyname";
    	$sql.= " FROM ".MAIN_DB_PREFIX."socpeople as sp";
    	if ($filtersarray[2] <> 'all')$sql.= " INNER JOIN ".MAIN_DB_PREFIX."categorie_contact as cs ON cs.fk_socpeople=sp.rowid";
        if ($filtersarray[2] <> 'all') $sql.= " INNER JOIN ".MAIN_DB_PREFIX."categorie as c ON cs.fk_categorie = c.rowid";
        
        
        if ($filtersarray[1] <> 'all')$sql.= " INNER JOIN ".MAIN_DB_PREFIX."categorie_societe as cs ON cs.fk_societe=sp.fk_soc";
        if ($filtersarray[1] <> 'all') $sql.= " INNER JOIN ".MAIN_DB_PREFIX."categorie as c ON cs.fk_categorie = c.rowid";
    	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = sp.fk_soc";
        $sql.= " WHERE sp.entity IN (".getEntity('societe', 1).")";
    	$sql.= " AND sp.email != ''";  // Note that null != '' is false
    	$sql.= " AND sp.no_email = 0";
    	
    	
    	//$sql.= " AND sp.poste != ''";
    	if ($filtersarray[0]<>'all') $sql.= " AND sp.poste ='".$this->db->escape($filtersarray[0])."'";
    	if ($filtersarray[2] <> 'all') $sql.= " AND c.label = '".$this->db->escape($filtersarray[2])."'";
        if ($filtersarray[1] <> 'all') $sql.= " AND c.label = '".$this->db->escape($filtersarray[1])."'";
    	$sql.= " ORDER BY sp.lastname, sp.firstname";
    	$resql = $this->db->query($sql);
    	if ($resql)
    	{
    		$num = $this->db->num_rows($resql);
    		$i = 0;
    		while ($i < $num)
    		{
    			$obj= $this->db->fetch_object($resql);
    			$target[] = array(
                            'email' => $obj->email,
                            'fk_contact' => $obj->fk_contact,
                            'lastname' => $obj->lastname,
                            'firstname' => $obj->firstname,
                            'other' =>
                                ($langs->transnoentities("ThirdParty").'='.$obj->companyname).';'.
                                ($langs->transnoentities("UserTitle").'='.($obj->civilite?$langs->transnoentities("Civility".$obj->civilite):'')),
                            'source_url' => $this->url($obj->id),
                            'source_id' => $obj->id,
                            'source_type' => 'contact'
							);
				$i++;
			}
		}

        return parent::add_to_target($mailing_id, $target);
    }

}

?>
