<?php

require "/home/sports/public_html/scripts/rico/plugins/php/dbClass3.php";
$GLOBALS['oDB'] = new dbClass();
if (! $GLOBALS['oDB']->MySqlLogon("sports_web", "sports_web", "group8") ) die('MySqlLogon failed');

require "/home/sports/public_html/scripts/rico/plugins/php/ricoResponse.php";

$id=isset($_GET["id"]) ? $_GET["id"] : "";
$oXmlResp= new ricoXmlResponse();
$errmsg='';
$query='';
$filters=array();

if (!isset($this->session->userdata($id))) {
  $errmsg="Session error. Please reload page. Your GET id var was $id and CI userdata is: ";
  $errmsg.=print_r($this->session->all_userdata(),1);
} else {
  $query=$this->session->userdata($id);
  $oXmlResp->SetDbConn($GLOBALS['oDB']);
  $oXmlResp->sendDebugMsgs=true;
  $oXmlResp->convertCharSet=true;
}
$oXmlResp->ProcessQuery($id, $query, $filters, $errmsg);
$oXmlResp=NULL;

$GLOBALS['oDB']->dbClose();
?>