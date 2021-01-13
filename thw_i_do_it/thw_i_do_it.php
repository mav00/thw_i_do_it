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
		$build = new IdiBuilder($table);
		$build->updateSelected($id, $field );
	}else if(isset($_POST['deleteDataSet'])){
		$table = $_POST['table'];
		$id = $_POST['ID'];
		$build = new IdiBuilder($table);
		$build->deleteDataSet($id);
	}else if(isset($_POST['addRow'])){
		$table = $_POST['table'];
		$build = new IdiBuilder($table);
		$build->insertDataSet( $_POST );
	}else if(isset($_POST['del_user'])){
		$table = $_POST['table'];
		$id = $_POST['ID'];
		$dbfield = $_POST['column'];
		$build = new IdiBuilder($table);
		$build->deleteSelected($id,$dbfield);
	}else if(isset($_POST['genPDF'])){
		$table = $_POST['table'];
		$build = new IdiBuilder($table);
		$build->genPDF();
	}

function getUserNameByID($userID)
{
	$user = get_userdata($userID);
	$_strName = $user ? $user->first_name . ' ' . $user->last_name : ''; 
	return $_strName;  
}

function isDateColumn($colName){
	return $colName == 'Datum';
}

class SetupTable{  
	private static $arraySetupTable = array();
	private $datatable = null;

    public static function getInstance($idiTable)
    {
        if (!array_key_exists($idiTable, self::$arraySetupTable)) {
			self::$arraySetupTable[$idiTable] = new SetupTable();
			$st = self::$arraySetupTable[$idiTable];

			$st->querryData($idiTable);
        }

        return self::$arraySetupTable[$idiTable];
	}
	
	function querryData($idiTable)
	{
		global $wpdb;
		$sqlStr= 'select * from ' . $wpdb->prefix . 'thw_idi_configuration where IdiTable=%s;'; 
		$this->datatable = $wpdb->get_row($wpdb->prepare($sqlStr,$idiTable));
	}

	function getWhere($table){
		
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
	
	extract(shortcode_atts( array(
		'tabelle' => 'ERROR',
	), $atts, 'thw' ));

	if($tabelle == 'ERROR'){
		return ' !!!Error: idiTable konnte nicht gefunden werden!!! ';
	}

	$build = new IdiBuilder($tabelle);

	return $build->buildPage();

}

class IdiBuilder{  

	private ?string $thwIdiWhere;
	private ?string $thwIdiOrderBy;
	private ?array $thwIdiFieldsArray;
	private ?array $thwIdiHeadersArray;
	private ?array $thwIdiSelectableFieldsArray;
	private ?array $thwIdiAdminUserIdsArray;
	private ?array $thwidiNotificationMailArray;
	private ?string $idiTable;
	private ?string $idiTable_With_Prefix;

	function __construct(string $_idiTable)
	{
		$this->idiTable = $_idiTable;
		$this->idiTable_With_Prefix = getTableWithPrefixSecure($_idiTable);
		//Where Statement
		//$thwIdiWhere 
		$this->thwIdiWhere = getDataFromSetupTable($_idiTable)->idiWhere;
		$this->thwIdiWhere = str_replace('&gt;', '>' , $this->thwIdiWhere);
		$this->thwIdiWhere = str_replace('&lt;', '<' , $this->thwIdiWhere);
		$this->thwIdiOrderBy= getDataFromSetupTable($_idiTable)->idiOrderBy;
		$this->thwIdiFieldsArray = explode(',',getDataFromSetupTable($_idiTable)->idiFields);
		$this->thwIdiHeadersArray = explode(',',getDataFromSetupTable($_idiTable)->idiHeaders);
		$this->thwIdiSelectableFieldsArray = explode(',',getDataFromSetupTable($_idiTable)->idiSelectableFields);
		$this->thwIdiAdminUserIdsArray = explode(',',getDataFromSetupTable($_idiTable)->idiAdminUserIds);
		$this->thwidiNotificationMailArray = explode(',',getDataFromSetupTable($_idiTable)->idiNotificationMail);
	}

	private function IsCurrentUserInRecord($RecordID){
		$data = self::getDataRowByID( $RecordID);
		return in_array(get_current_user_id(), (array) $data);
	}

	function getDataPretty($DataRaw, $head)
	{
		$isSelectableField = self::isSelectableField($head);

		if($isSelectableField && $DataRaw != '-1') { 
			$DataRaw =  getUserNameByID($DataRaw); 
		}else if( $DataRaw != "-1" ) { 
			$DataRaw = $DataRaw; 
		}else { 
			$DataRaw = ''; 
		}
		if(isDateColumn($head) && isset($DataRaw) && $DataRaw != '' )
		{
			$gerDatum = new DateTime($DataRaw);
			$DataRaw = $gerDatum->format('d.m.Y');
		}
		return $DataRaw;
	}

	function genPDF()
	{
		global $wpdb;
		global $wp;	
		require('fpdf.php');

		$pdf = new FPDF('L');
		$pdf->AddPage();
		$pdf->SetFont('Arial','B',16);
		$pdf->Cell(40,10,strtoupper($this->idiTable) . '-Dienste');
		$pdf->Ln();

		$sqlStr = 'SELECT * FROM ' . $this->idiTable_With_Prefix . ' ' . $this->thwIdiWhere . ' ' . $this->thwIdiOrderBy .';';
		$thw_daten = $wpdb->get_results($sqlStr);
		$xval = array_combine($this->thwIdiHeadersArray, $this->thwIdiFieldsArray);
		//var_dump($this->thwIdiHeadersArray);
		// Column widths
		$w = array( 20, 15, 10, 38, 38, 38, 38, 38, 38);
		// Header
		$pdf->SetFont('Arial','B',8);
		for($i=0;$i<count($w);$i++){
			$str = $this->thwIdiHeadersArray[$i];
			$pdf->Cell($w[$i],9,$str,1,0,'C');
		}
		$pdf->Ln();
		//Data
		$pdf->SetFont('Arial','',8);
		foreach ($thw_daten as $thwdatum) {
			for($j=0;$j<count($w);$j++){
				$head = $this->thwIdiFieldsArray[$j]; 
				$str = $thwdatum->$head;
				$str= self::getDataPretty($str,$head);
				//echo $str;
				$pdf->Cell($w[$j],9,$str,1,0,'C');
			}
			$pdf->Ln();	
		}
		$pdf->Output('D',$this->idiTable . '_Dienste.pdf',false);
	}

	function buildPage(){
		global $wpdb;
		global $wp;	
	
		/* collect some Vars */
		$hugeRetString = ''; /* everything collected in this string to return the HTML on the right spot of the document */
		$thisPage = get_permalink(get_the_ID()); ; // home_url(add_query_arg(array($_GET), $wp->request));//plugins_url( 'thw_i_do_it.php', __FILE__ ); 
		$sqlStr = 'SELECT * FROM ' . $this->idiTable_With_Prefix . ' ' . $this->thwIdiWhere . ' ' . $this->thwIdiOrderBy .';';
		$thw_daten = $wpdb->get_results($sqlStr);
	
		$xval = array_combine($this->thwIdiHeadersArray, $this->thwIdiFieldsArray);
		/*build Table*/
		$hugeRetString .=  '<div>';
		
		/*build table header*/
		
		// build Data Rows
		if( $wpdb->num_rows > 0){
			foreach ($thw_daten as $thwdatum) {
				$hugeRetString .=  '<div><table>';
				
				$userAlreadyInThatRecord = self::IsCurrentUserInRecord($thwdatum->ID);
				
				foreach($xval as $header => $dbfield){
					$isSelectableField = self::isSelectableField($dbfield);
					//debug: echo $thwdatum->Datum;
					//debug: var_dump($thwdatum);
					$isEmptySelectField  = ($thwdatum->$dbfield == Null || $thwdatum->$dbfield < 0) && $isSelectableField;
					$isFilledSelectField  = ($thwdatum->$dbfield != NULL && $thwdatum->$dbfield >=0) && $isSelectableField;
					$hugeRetString .= '<tr>';
					$hugeRetString .= '<td><b>' .$header . ': &nbsp</b></td>';
					if( $isEmptySelectField && !$userAlreadyInThatRecord)
					{
						// if there is no entry bild the button to register
						$hugeRetString .= '<td><form method="POST" onsubmit="return confirm(\'Der Eintrag ist verbindlich. Eintrag vornehmen? \');" action="' . $thisPage . '">';
						$hugeRetString .= '<input type="hidden" name="table" value="' . $this->idiTable . '" />';
						$hugeRetString .= '<input type="hidden" name="ID" value="' . $thwdatum->ID . '" />';
						$hugeRetString .= '<input type="hidden" name="column" value="' . $dbfield . '" />';
						$hugeRetString .= '<input type="submit" class="button" value="mich eintragen" name="insert_me"/>' ;
						$hugeRetString .= '</form></td>';
					}else if ($isFilledSelectField && self::isAdmin()){
						// add delete entry for Admins but only in edit fields
						$hugeRetString .= '<td>' . getUserNameByID($thwdatum->$dbfield)	 . '<form method="POST" onsubmit="return confirm(\'Soll der Eintrag wirklich gelöscht werden?\');" action="' . $thisPage . '">';
						$hugeRetString .= '<input type="hidden" name="table" value="' . $this->idiTable . '" />';
						$hugeRetString .= '<input type="hidden" name="ID" value="' . $thwdatum->ID . '" />';
						$hugeRetString .= '<input type="hidden" name="column" value="' . $dbfield . '" />';
						$hugeRetString .= '<input type="submit" class="button" value="Löschen" name="del_user"  />' ;
						$hugeRetString .= '</form></td>';
					}
					else {
						// if there is something in the Database show it
						$tabval = self::getDataPretty($thwdatum->$dbfield, $dbfield);
						$hugeRetString .=  '<td>' . $tabval . '</td>'  ;
					}
					$hugeRetString .= '</tr>';	
				}
				if(self::isAdmin()) // add the Delete Row button for Admins
				{
					$hugeRetString .= '<tr><td colspan=2><form method="POST" onsubmit="return confirm(\'Soll der Datensatz wirklich gelöscht werden?\');" action="' . $thisPage . '">';
					$hugeRetString .= '<input type="hidden" name="table" value="' . $this->idiTable . '" />';
					$hugeRetString .= '<input type="hidden" name="ID" value="' . $thwdatum->ID . '" />';
					$hugeRetString .= '<input type="submit" class="button" value="Diesen Datensatz löschen" name="deleteDataSet"/>' ;
					$hugeRetString .= '</form></td></tr>';
				}
				//end the Row
				$hugeRetString .= '</table><hr></div>';
			}
		}
		if(self::isAdmin()){
			$hugeRetString .= '<div><b>Neuen Datensatz eintragen: </b></br>';
			$hugeRetString .= '<form method="POST" action="' . $thisPage . '"><table>';
			foreach($xval as $header => $field)
			{	
				$hugeRetString .= '<tr>' ;
				if(!in_array($field, $this->thwIdiSelectableFieldsArray)){
					$hugeRetString .= '<td><b>' . $header . ':</b></td><td><input type="text" name="' . $field .'" value="" /> </td>';
				}
				$hugeRetString .= '</tr>' ;
			}
			$hugeRetString .= '</table>';
			$hugeRetString .= '<input type="hidden" name="table" value="' . $this->idiTable . '" />' ;
			$hugeRetString .= '<input type="submit" class="button" value="hinzufügen" name="addRow"/></form><br>';
			$hugeRetString .= '<form method="POST" action="' . $thisPage . '">';
			$hugeRetString .= '<input type="hidden" name="table" value="' . $this->idiTable . '" />';
			$hugeRetString .= '<input type="submit" class="button" value="PDF generieren" name="genPDF"/>' ;
			$hugeRetString .= '</form><hr></div>' ;
			
		}
		// todo: add new ROW THV Admins
		$hugeRetString .= '</table></div>';
		return $hugeRetString;
	}
	
	function updateSelected( $id, $field )
	{
		global $wpdb;
		$wpdb->update(getTableWithPrefixSecure($this->idiTable) , array($field => get_current_user_id()), array('ID' => $id ));
		self::mailNewEntry(get_current_user_id(),$id);
	}

	function deleteSelected($id, $field )
	{
		if(!self::isAdmin())
		{
			echo 'ungenügend Rechte';
			return;
		}
		self::mailDeleteEntry($field,$id);
		global $wpdb;
		$wpdb->update(getTableWithPrefixSecure($this->idiTable) , array($field => -1), array('ID' => $id ));
	}

	function deleteDataSet( $id )
	{
		if(!self::isAdmin())
		{
			echo 'ungenügend Rechte';
			return;
		}
		global $wpdb;
		//$dataValue = '';
		$sqlStr = 'Delete from ' . getTableWithPrefixSecure($this->idiTable) . ' WHERE ID = %d' ;
		$wpdb->query($wpdb->prepare($sqlStr,$id));	 //prepare disallows SQL injection
	}
	
	function insertDataSet($fieldValueArray )
	{
		//adjust date format
		$gerDate = new DateTime($fieldValueArray['Datum']);
		$fieldValueArray['Datum'] = $gerDate->format('Y-m-d');
		//TODO: check Datum
		global $wpdb;
		if(!self::isAdmin())
		{
			echo 'ungenügend Rechte';
			return;
		}

		$data = [];
		unset($fieldValueArray["table"]);
		unset($fieldValueArray["addRow"]);
		$wpdb->insert(getTableWithPrefixSecure($this->idiTable) , $fieldValueArray, array( '%s','%s' ));
	}

	function isAdmin($userID = -1){
		if ($userID == -1) {
			$userID = wp_get_current_user();
		}
		if(array_key_exists('noadmin',$_GET)) //for testing	turn of admin
		{
			return false;
		}
		//check if the userid is are stored in the tabel thw_idi_configuration ... so he is admin!

		$isAdmin = in_array( $userID->ID,$this->thwIdiAdminUserIdsArray);
		return $isAdmin;
	}

	function mailNewEntry( $userID, $datasetId)
	{	
		$datarow = self::getDataRowByID($datasetId); 
		$subject = 'THW Diensteintrag: Neuer Nutzereintrag in idiTable ' . $this->idiTable; 
		$message = 'Der Benutzer ' .  getUserNameByID($userID) . ' hat sich in der idiTable: ' . $this->idiTable . ' für folgenden den Dienst eingetragen:' . PHP_EOL;
		foreach ($datarow as $head => $value)
		{
			if(self::isSelectableField( $head ))
			{
				if ($userID == $value)
				{
					$value = '---> ' . getUserNameByID($value) . ' <---';
				}else{
					$value = getUserNameByID($value);
				}
			}
			$message .= $head . ': ' . $value . PHP_EOL;
		}
		wp_mail( $this->thwidiNotificationMailArray, $subject, $message );
	}

	function mailDeleteEntry( $field, $datasetId)
	{
		$datarow = self::getDataRowByID($datasetId);
		$userID = $datarow->$field;  
		$subject = 'THW Diensteintrag: Neue Austragung aus einen ' . $this->idiTable . '-Dienst'; 
		$message = 'Der ' . getUserNameByID(get_current_user_id())  . ' hat ' .  getUserNameByID($userID) . '  für folgeneden: ' . $this->idiTable . '-Dienst ausgetragen:' . PHP_EOL;
		foreach ($datarow as $head => $value)
		{
			if(self::isSelectableField( $head ))
			{
				if ($userID == $value)
				{
					$value = '---> ' . getUserNameByID($value) . ' <---';
				}else{
					$value = getUserNameByID($value);
				}
			}
			$message .= $head . ': ' . $value . PHP_EOL;
		}
		wp_mail($this->thwidiNotificationMailArray, $subject, $message );
	}

	function getDataRowByID($ID)
	{
		global $wpdb;
		$sqlStr = 'SELECT * FROM ' . getTableWithPrefixSecure($this->idiTable) . ' where ID=%d ;';
		$datarow = $wpdb->get_row($wpdb->prepare($sqlStr,$ID));
		return $datarow;
	}

	function isSelectableField( $dbfield)
	{
		$isSelectableField = in_array($dbfield, $this->thwIdiSelectableFieldsArray);
		return $isSelectableField;
	}
}

//TODO: check this in constructor and thats it
function getTableWithPrefixSecure($tab)
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






?>