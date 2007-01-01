<?php

/**
 * Internationalisation file for CountEdits extension
 *
 * @package MediaWiki
 * @subpackage Extensions
 * @author Bertrand GRONDIN <bertrand.grondinr@tiscali.fr>
 */
 
function efEditCountMessages() {
	$messages = array(
// English Version by Rob Church
	'en' => array(
	'editcount' => 'Edit count',
	'editcount_username' => 'User: ',
	'editcount_submit' => 'Submit',
	'editcount_total' => 'Total',
	),
// French Version by Bertrand Grondin
	'fr' => array(
	'editcount' => 'Compteur d\'éditions individuel',
	'editcount_username' => 'Utilisateur: ',
	'editcount_submit' => 'Soumettre',
	'editcount_total' => 'Total',
	),
// German by Leon Weber
	'de' => array(
	'editcount' => 'Anzahl der Seitenbearbeitungen',
	'editcount_username' => 'Benutzer: ',
	'editcount_submit' => 'Absenden',
	'editcount_total' => 'Gesamt',
	),
);	
	return $messages;
}
