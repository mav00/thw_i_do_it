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
		$submitted_array = ($_POST['insert_me']);
		$table = $_POST['table'];
		$id = $_POST['ID'];
		$field = $_POST['column'];
		updateSelected($table, $id, $field );
		//echo $table . $id . $field;	
		//echo("You clicked button one!");
		
        //and then execute a sql query here
	}else if(isset($_POST['deleteRow'])){
		$submitted_array = ($_POST['deleteRow']);
		$table = $_POST['table'];
		$id = $_POST['ID'];
		deleteRow($table, $id);
	}else if(isset($_POST['addRow'])){
		$submitted_array = ($_POST['addRow']);
		$table = $_POST['table'];
		insertData( $table, $_POST );
	}else if(isset($_POST['del_user'])){
		$table = $_POST['table'];
		$id = $_POST['ID'];
		$dbfield = $_POST['column'];
		deleteSelected($table,$id,$dbfield);
	}

function getUsername(){
	global $current_user;
	$user = wp_get_current_user();
	$_strName = $user->first_name . ' ' . $user->last_name; 
	return $_strName;  
}	


function getDataFromSetupTable($table){
	global $wpdb;
	$sqlStr= 'select * from wp_thw_idi_configuration where IdiTable="' . $table . '";'; 
	return $wpdb->get_row($sqlStr);
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
		$tabelleWithPrefix = $wpdb->prefix . 'thw_idi_' .  $tabelle;
	}


	//Where Statement
	//$thwIdiWhere 
	$thwIdiWhere= getDataFromSetupTable($tabelle)->idiWhere;
	$thwIdiWhere = str_replace('&gt;', '>' , $thwIdiWhere);
	$thwIdiWhere = str_replace('&lt;', '<' , $thwIdiWhere);

	//Field Statement
	$thwIdiFields = getDataFromSetupTable($tabelle)->idiFields;

	//Field Statement
	$thwIdiHeaders = getDataFromSetupTable($tabelle)->idiHeaders;

	//Field Statement
	$thwIdiSelectableFields = getDataFromSetupTable($tabelle)->idiSelectableFields;

	//Field Statement
	$thwIdiAdminRole = getDataFromSetupTable($tabelle)->idiAdminRole;
	
	//Is the current user an Admin so we do options more fore him;
	$isAdmin = isAdmin($tabelle);
	//$isAdmin = false;
	/* collect some Vars */
	$hugeRetString = ''; /* everything collected in this string to return the HTML on the right spot of the document */
	$thisPage = get_permalink(get_the_ID()); ; // home_url(add_query_arg(array($_GET), $wp->request));//plugins_url( 'thw_i_do_it.php', __FILE__ ); 
	$sqlStr = 'SELECT * FROM ' . $tabelleWithPrefix . ' ' . $thwIdiWhere .';' ;
	$thw_daten = $wpdb->get_results($sqlStr);

	$thwIdiFieldsArray = explode(',',$thwIdiFields);
	$thwIdiHeadersArray = explode(',',$thwIdiHeaders);
	$thwIdiSelectableFieldsArray = explode(',',$thwIdiSelectableFields);
	
	/*build Table*/
	$hugeRetString .=  '<div><table class=thwtable>';
	
	/*build table header*/
	$hugeRetString .= '<tr>';
	if($isAdmin) // extra column for Admins
	{
		$hugeRetString .= '<td>Admin</td>';
	}
	foreach($thwIdiHeadersArray as $headkey => $headfield){
		$hugeRetString .= '<td>' . $headfield . '</td>';
	}
	$hugeRetString .= '</tr>';
	
	// build Data Rows
	if( $wpdb->num_rows > 0){
		foreach ($thw_daten as $thwdatum) {
			$hugeRetString .= '<tr>';
			if($isAdmin) // add the Delete Row button for Admins
			{
				$hugeRetString .= '<td><form method="POST" onsubmit="return confirm(\'Soll der Datensatz wirklich gelöscht werden?\');" action="' . $thisPage . '">';
				$hugeRetString .= '<input type="hidden" name="table" value="' . $tabelle . '" />';
				$hugeRetString .= '<input type="hidden" name="ID" value="' . $thwdatum->ID . '" />';
				$hugeRetString .= '<input type="submit" class="button" value="delete" name="deleteRow"/>' ;
				$hugeRetString .= '</form></td>';
			}

			$userIsInRow = chechIfInList($tabelle,$thwdatum->ID,getUsername());
			foreach($thwIdiFieldsArray as $key => $dbfield){
				//debug: echo $thwdatum->Datum;
				//debug: var_dump($thwdatum);
				$isEmptySelectField  = ($thwdatum->$dbfield == '_NutzerEintragen_' || $thwdatum->$dbfield == '') && in_array($dbfield, $thwIdiSelectableFieldsArray);
				$isFilledSelectField  = ($thwdatum->$dbfield != '_NutzerEintragen_' && $thwdatum->$dbfield != '') && in_array($dbfield, $thwIdiSelectableFieldsArray);
				if( $isEmptySelectField && !$userIsInRow)
				{
					// if there is no entry bild the button to register
					$hugeRetString .= '<td><form method="POST" onsubmit="return confirm(\'Der Eintrag ist verbindlich. Eintrag vornehmen? \');" action="' . $thisPage . '">';
					$hugeRetString .= '<input type="hidden" name="table" value="' . $tabelle . '" />';
					$hugeRetString .= '<input type="hidden" name="ID" value="' . $thwdatum->ID . '" />';
					$hugeRetString .= '<input type="hidden" name="column" value="' . $dbfield . '" />';
					$hugeRetString .= '<input type="submit" class="button" value="mich eintragen" name="insert_me"/>' ;
					$hugeRetString .= '</form></td>';
				}else if ($isFilledSelectField && $isAdmin){
					// add delete entry for Admins but only in edit fields
					$hugeRetString .= '<td>' . $thwdatum->$dbfield . '<form method="POST" onsubmit="return confirm(\'Soll der Eintrag wirklich gelöscht werden?\');" action="' . $thisPage . '">';
					$hugeRetString .= '<input type="hidden" name="table" value="' . $tabelle . '" />';
					$hugeRetString .= '<input type="hidden" name="ID" value="' . $thwdatum->ID . '" />';
					$hugeRetString .= '<input type="hidden" name="column" value="' . $dbfield . '" />';
					$hugeRetString .= '<input type="submit" class="button" value="Löschen" name="del_user"  />' ;
					$hugeRetString .= '</form></td>';
				}
				else {
					// if there is something in the Database show it
					$tabval = ($thwdatum->$dbfield == '_NutzerEintragen_')? '': $thwdatum->$dbfield;
					$hugeRetString .= '<td>' . $tabval . '</td>' ;
				}
			}

			//end the Row
			$hugeRetString .= '</tr>';
		}
	}
	if($isAdmin){
		$hugeRetString .= '<tr><form method="POST" action="' . $thisPage . '">';
		$hugeRetString .= '<td><input type="hidden" name="table" value="' . $tabelle . '" />' ;
		$hugeRetString .= '<input type="submit" class="button" value="hinzufügen" name="addRow"/> </td>' ;
		foreach($thwIdiFieldsArray as $key => $field)
		{

			$hugeRetString .= '<td>' ;
			if(!in_array($field, $thwIdiSelectableFieldsArray)){
				$hugeRetString .= '<input type="text" name="' . $field .'" value="" />';
			}
			$hugeRetString .= '</td>' ;
		}
		$hugeRetString .= '</form></tr>';
	}
	// todo: add new ROW THV Admins
	$hugeRetString .= '</table></div>';
	return $hugeRetString;
}	


function deleteSelected( $tab, $id, $field )
{
	if(!isAdmin($tab))
	{
		echo 'ungenügend Rechte';
		return;
	}
	global $wpdb;
	$sqlStr = 'UPDATE ' . getTableWithPrefix($tab) . ' SET ' . $field . '="_NutzerEintragen_" WHERE ID = %d' ;
	//echo $sqlStr . '<br>';
	$wpdb->query($wpdb->prepare($sqlStr,$id));	//prepare disallows SQL injection
}

function chechIfInList($tab,$id,$user){
	global $wpdb;
	
	$sqlStr = 'select * from ' . getTableWithPrefix($tab) .' where id = %d';
	$data = $wpdb->get_row($wpdb->prepare($sqlStr,$id));
	return in_array($user, (array) $data);
}

function getTableWithPrefix($tab)
{
	global $wpdb;
	$tabelleWithPrefix = $wpdb->prefix . 'thw_idi_' .  $tab;
	if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $tabelleWithPrefix ) ) !== $tabelleWithPrefix ) {
		echo "fehler in der Abfrage";
		$tabelleWithPrefix = 'ERROR';
	} 
	return $tabelleWithPrefix;
}

function updateSelected( $tab, $id, $field )
{
	//todo: check if user is allready somewhere in that row

	global $wpdb;
	
	$wpdb->update(getTableWithPrefix($tab) , array($field => getUsername()), array('ID' => $id ));
}

function deleteRow( $tab, $id )
{
	if(!isAdmin($tab))
	{
		echo 'ungenügend Rechte';
		return;
	}
	//todo: check if user is allowed to do that!!!
	global $wpdb;
	//$dataValue = '';
	$sqlStr = 'Delete from ' . getTableWithPrefix($tab) . ' WHERE ID = %d' ;
	$wpdb->query($wpdb->prepare($sqlStr,$id));	 //prepare disallows SQL injection
}

function insertData( $tab, $fieldValueArray )
{
	global $wpdb;
	if(!isAdmin($tab))
	{
		echo 'ungenügend Rechte';
		return;
	}
	$data = [];
	unset($fieldValueArray["table"]);
	unset($fieldValueArray["addRow"]);

	$tabelleWithPrefix = $wpdb->prefix . 'thw_idi_' .  $tab;
	$wpdb->insert($wpdb->prefix . "thw_idi_" . $tab , $fieldValueArray, array( '%s','%s' ));
}


function isAdmin($tabelle){
	global $wpdb;
	//check if the user has the rights wich are stored in the tabel thw_idi_configuration if so he is admin!
	$result = $wpdb->get_row($wpdb->prepare('SELECT idiAdminRole FROM ' . $wpdb->prefix . 'thw_idi_configuration WHERE idiTable = "%s"',$tabelle));
	$isAdmin = in_array( $result->idiAdminRole, (array) wp_get_current_user()->roles);
	return $isAdmin;
}

?>