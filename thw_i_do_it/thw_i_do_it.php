<?php 
      /*
      Plugin Name: THW I do it
      Plugin URI: https://github.com/mav00/thw_i_do_it/
      Description: voting on a task to do it
      Author: Matthias Verwold
      Version: 0.2
      Author URI: http://www.verwold.name
	  */
	
		
	add_shortcode( 'thw_idi', 'thw_plugin_main' );

	/* include Wordpress user management */
  	if(!function_exists('wp_get_current_user')) {
		include(ABSPATH . "wp-includes/pluggable.php"); 
	}

	/*get if there is a sumit button presed*/
    if(isset($_POST['insert_me'])){
		$table = $_POST['table'];
		$id = $_POST['ID'];
		$field = $_POST['column'];
		updateSelected($table, $id, $field );
	}else if(isset($_POST['deleteRow'])){
		$table = $_POST['table'];
		$id = $_POST['ID'];
		deleteRow($table, $id);
	}else if(isset($_POST['addRow'])){
		$table = $_POST['table'];
		insertData( $table, $_POST );
	}else if(isset($_POST['del_user'])){
		$table = $_POST['table'];
		$id = $_POST['ID'];
		$dbfield = $_POST['column'];
		deleteSelected($table,$id,$dbfield);
	}

function getUserNameByID($userID)
{
	$user = get_userdata($userID);
	$_strName = $user ? $user->first_name . ' ' . $user->last_name : ''; 
	return $_strName;  
}

class SetupTable{  
	private static $arraySetupTable = array();
	private $datatable = null;

    public static function getInstance($tabelle)
    {
        if (!array_key_exists($tabelle, self::$arraySetupTable)) {
			self::$arraySetupTable[$tabelle] = new SetupTable();
			$st = self::$arraySetupTable[$tabelle];

			$st->querryData($tabelle);
        }

        return self::$arraySetupTable[$tabelle];
	}
	
	function querryData($tabelle)
	{
		global $wpdb;
		$sqlStr= 'select * from ' . $wpdb->prefix . 'thw_idi_configuration where IdiTable=%s;'; 
		$this->datatable = $wpdb->get_row($wpdb->prepare($sqlStr,$tabelle));
	}

	function data()
	{
		return $this->datatable;
	}

    private function __construct()
    {
    }

    private function __clone()
    {
    }

    private function __wakeup()
    {
    }
}


function getDataFromSetupTable($table){
	return SetupTable::getInstance($table)->data();
}

function thw_plugin_main( $atts ) {
	global $wpdb;
	global $wp;	

	$dodebug = true;
	//if(dodebug) var_dump($atts);
	extract(shortcode_atts( array(
		'tabelle' => 'ERROR',
	), $atts, 'thw' ));

	if($tabelle == 'ERROR'){
		return ' !!!Error: Tabelle konnte nicht gefunden werden!!! ';
	}else{
		$tabelleWithPrefix = getTableWithPrefix($tabelle);
	}

	//Where Statement
	//$thwIdiWhere 
	$thwIdiWhere= getDataFromSetupTable($tabelle)->idiWhere;
	$thwIdiWhere = str_replace('&gt;', '>' , $thwIdiWhere);
	$thwIdiWhere = str_replace('&lt;', '<' , $thwIdiWhere);

	$thwIdiOrderBy= getDataFromSetupTable($tabelle)->idiOrderBy;
	
	//Field Statement
	$thwIdiFields = getDataFromSetupTable($tabelle)->idiFields;

	//Field Statement
	$thwIdiHeaders = getDataFromSetupTable($tabelle)->idiHeaders;

	//Field Statement
	$thwIdiSelectableFields = getDataFromSetupTable($tabelle)->idiSelectableFields;

	//Field Statement
	$thwIdiAdminUserIds = explode(',',getDataFromSetupTable($tabelle)->idiAdminUserIds);
	
	//Is the current user an Admin so we do options more fore him;
	$isAdmin = isAdmin($tabelle);
	//$isAdmin = false;
	/* collect some Vars */
	$hugeRetString = ''; /* everything collected in this string to return the HTML on the right spot of the document */
	$thisPage = get_permalink(get_the_ID()); ; // home_url(add_query_arg(array($_GET), $wp->request));//plugins_url( 'thw_i_do_it.php', __FILE__ ); 
	$sqlStr = 'SELECT * FROM ' . $tabelleWithPrefix . ' ' . $thwIdiWhere . ' ' . $thwIdiOrderBy .';';
	$thw_daten = $wpdb->get_results($sqlStr);

	$thwIdiFieldsArray = explode(',',$thwIdiFields);
	$thwIdiHeadersArray = explode(',',$thwIdiHeaders);
	$thwIdiSelectableFieldsArray = explode(',',$thwIdiSelectableFields);
	$xval = array_combine($thwIdiHeadersArray, $thwIdiFieldsArray);
	/*build Table*/
	$hugeRetString .=  '<div>';
	
	/*build table header*/
	
	// build Data Rows
	if( $wpdb->num_rows > 0){
		foreach ($thw_daten as $thwdatum) {
			$hugeRetString .=  '<div><table>';
			
			$userIsInRow = checkIfInList($tabelle,$thwdatum->ID);
			
			foreach($xval as $header => $dbfield){
				$isSelectableField = in_array($dbfield, $thwIdiSelectableFieldsArray);
				//debug: echo $thwdatum->Datum;
				//debug: var_dump($thwdatum);
				$isEmptySelectField  = ($thwdatum->$dbfield == Null || $thwdatum->$dbfield < 0) && $isSelectableField;
				$isFilledSelectField  = ($thwdatum->$dbfield != NULL && $thwdatum->$dbfield >=0) && $isSelectableField;
				$hugeRetString .= '<tr>';
				if( $isEmptySelectField && !$userIsInRow)
				{
					$hugeRetString .= '<td><b>' .$header . ': &nbsp</b></td>';
					// if there is no entry bild the button to register
					$hugeRetString .= '<td><form method="POST" onsubmit="return confirm(\'Der Eintrag ist verbindlich. Eintrag vornehmen? \');" action="' . $thisPage . '">';
					$hugeRetString .= '<input type="hidden" name="table" value="' . $tabelle . '" />';
					$hugeRetString .= '<input type="hidden" name="ID" value="' . $thwdatum->ID . '" />';
					$hugeRetString .= '<input type="hidden" name="column" value="' . $dbfield . '" />';
					$hugeRetString .= '<input type="submit" class="button" value="mich eintragen" name="insert_me"/>' ;
					$hugeRetString .= '</form></td>';
				}else if ($isFilledSelectField && $isAdmin){
					$hugeRetString .= '<td><b>' .$header . ': &nbsp</b></td>';
					// add delete entry for Admins but only in edit fields
					$hugeRetString .= '<td>' . getUserNameByID($thwdatum->$dbfield)	 . '<form method="POST" onsubmit="return confirm(\'Soll der Eintrag wirklich gelöscht werden?\');" action="' . $thisPage . '">';
					$hugeRetString .= '<input type="hidden" name="table" value="' . $tabelle . '" />';
					$hugeRetString .= '<input type="hidden" name="ID" value="' . $thwdatum->ID . '" />';
					$hugeRetString .= '<input type="hidden" name="column" value="' . $dbfield . '" />';
					$hugeRetString .= '<input type="submit" class="button" value="Löschen" name="del_user"  />' ;
					$hugeRetString .= '</form></td>';
				}
				else {
					// if there is something in the Database show it
					$hugeRetString .= '<td><b>' .$header . ': &nbsp</b></td>';
					if($isSelectableField && $thwdatum->$dbfield != '-1') { 
						$tabval =  getUserNameByID($thwdatum->$dbfield); 
					}else if( $thwdatum->$dbfield != "-1" ) { 
						$tabval = $thwdatum->$dbfield; 
					}else { 
						$tabval = ''; 
					}
					if(isDateColumn($header) && isset($tabval) && $tabval != '' )
					{
						$gerDatum = new DateTime($tabval);
						$tabval = $gerDatum->format('d.m.Y');
					}
					$hugeRetString .=  '<td>' . $tabval . '</td>'  ;
				}
				$hugeRetString .= '</tr>';	
			}
			if($isAdmin) // add the Delete Row button for Admins
			{
				$hugeRetString .= '<tr><td colspan=2><form method="POST" onsubmit="return confirm(\'Soll der Datensatz wirklich gelöscht werden?\');" action="' . $thisPage . '">';
				$hugeRetString .= '<input type="hidden" name="table" value="' . $tabelle . '" />';
				$hugeRetString .= '<input type="hidden" name="ID" value="' . $thwdatum->ID . '" />';
				$hugeRetString .= '<input type="submit" class="button" value="Diesen Datensatz löschen" name="deleteRow"/>' ;
				$hugeRetString .= '</form></td></tr>';
			}
			//end the Row
			$hugeRetString .= '</table><hr></div>';
		}
	}
	if($isAdmin){
		$hugeRetString .= '<div><b>Neuen Datensatz eintragen: </b></br>';
		$hugeRetString .= '<form method="POST" action="' . $thisPage . '"><table>';
		foreach($xval as $header => $field)
		{	
			$hugeRetString .= '<tr>' ;
			if(!in_array($field, $thwIdiSelectableFieldsArray)){
				$hugeRetString .= '<td><b>' . $header . ':</b></td><td><input type="text" name="' . $field .'" value="" /> </td>';
			}
			$hugeRetString .= '</tr>' ;
		}
		$hugeRetString .= '</table>';
		$hugeRetString .= '<input type="hidden" name="table" value="' . $tabelle . '" />' ;
		$hugeRetString .= '<input type="submit" class="button" value="hinzufügen" name="addRow"/></form><br><hr></div>' ;
		
	}
	// todo: add new ROW THV Admins
	$hugeRetString .= '</table></div>';
	return $hugeRetString;
}	

function checkIfInList($tab,$DBid){
	global $wpdb;
	$sqlStr = 'select * from ' . getTableWithPrefix($tab) .' where id = %d';
	$data = $wpdb->get_row($wpdb->prepare($sqlStr,$DBid));
	return in_array(get_current_user_id(), (array) $data);
}

function getTableWithPrefix($tab)
{
	global $wpdb;
	//check if Table  is one of ours 
	$sqlStr = 'select idiTable from ' . $wpdb->prefix . 'thw_idi_configuration';
	$data = $wpdb->get_row($sqlStr);
	if(in_array($tab, (array)$data))
	{
		return $wpdb->prefix . 'thw_idi_' .  $tab;
	}

	return NULL;
}

function updateSelected( $tab, $id, $field )
{
	global $wpdb;
	$wpdb->update(getTableWithPrefix($tab) , array($field => get_current_user_id()), array('ID' => $id ));
	mailNewEntry($tab,get_current_user_id(),$id);
}

function deleteSelected( $tab, $id, $field )
{
	if(!isAdmin($tab))
	{
		echo 'ungenügend Rechte';
		return;
	}
	global $wpdb;
	$wpdb->update(getTableWithPrefix($tab) , array($field => -1), array('ID' => $id ));
}


function deleteRow( $tab, $id )
{
	if(!isAdmin($tab))
	{
		echo 'ungenügend Rechte';
		return;
	}
	global $wpdb;
	//$dataValue = '';
	$sqlStr = 'Delete from ' . getTableWithPrefix($tab) . ' WHERE ID = %d' ;
	$wpdb->query($wpdb->prepare($sqlStr,$id));	 //prepare disallows SQL injection
}

function insertData( $tab, $fieldValueArray )
{
	//adjust date format
	$gerDate = new DateTime($fieldValueArray['Datum']);
	$fieldValueArray['Datum'] = $gerDate->format('Y-m-d');

	global $wpdb;
	if(!isAdmin($tab))
	{
		echo 'ungenügend Rechte';
		return;
	}
	$data = [];
	unset($fieldValueArray["table"]);
	unset($fieldValueArray["addRow"]);
	$wpdb->insert(getTableWithPrefix($tab) , $fieldValueArray, array( '%s','%s' ));
}


function isAdmin($tabelle){
	global $wpdb;
	//check if the user has the rights wich are stored in the tabel thw_idi_configuration if so he is admin!
	$result = $wpdb->get_row($wpdb->prepare('SELECT idiAdminUserIds FROM ' . $wpdb->prefix . 'thw_idi_configuration WHERE idiTable = "%s"',$tabelle));
	$userids = explode(',',$result->idiAdminUserIds);
	$isAdmin = in_array( wp_get_current_user()->ID,$userids);
	return $isAdmin;
}

function isDateColumn($colName){
	return $colName == 'Datum';
}

function isSelectableField($tabelle, $dbfield)
{
	$thwIdiSelectableFields = getDataFromSetupTable($tabelle)->idiSelectableFields;
	$thwIdiSelectableFieldsArray = explode(',',$thwIdiSelectableFields);
	$isSelectableField = in_array($dbfield, $thwIdiSelectableFieldsArray);
	return $isSelectableField;
}

function mailNewEntry($tab, $userID, $datasetId)
{
	global $wpdb;
	//echo $tab .' ';
	//echo $userID .' ';
	//echo $datasetId .' ';
	$mails = getDataFromSetupTable($tab);
	$mailTo = explode(',',$mails->idiNotificationMail);

	$sqlStr = 'SELECT * FROM ' . getTableWithPrefix($tab) . ' where ID=%d ;';
	$datarow = $wpdb->get_row($wpdb->prepare($sqlStr,$datasetId)); 
	//var_dump ($datarow); 
	//wp_mail( string|array $to, string $subject, string $message, string|array $headers = '', string|array $attachments = array() )
	$subject = 'THW Diensteintrag: Neuer Nutzereintrag in Tabelle ' . $tab; 
	$message = 'Der Benutzer ' .  getUserNameByID($userID) . ' hat sich in der Tabelle: ' . $tab . ' für folgenden den Dienst eingetragen:' . PHP_EOL;
	foreach ($datarow as $head => $value)
	{
		//echo 'xxxxxx ' . $tab . ' ' . $head . ' ';
		if(isSelectableField($tab, $head ))
		{
			$value = getUserNameByID($value);
		}
		$message .= $head . ': ' . $value . PHP_EOL;
	}
	//echo $mailTo;
	//echo $subject;
	//echo $message;
	wp_mail( $mailTo, $subject, $message );
}

?>