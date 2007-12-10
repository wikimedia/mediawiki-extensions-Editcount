<?php
if (!defined('MEDIAWIKI')) die();
/**
 * A Special Page extension that displays edit counts.
 *
 * This page can be accessed from Special:Editcount[/user] as well as being
 * included like {{Special:Editcount/user[/namespace]}}
 *
 * @addtogroup Extensions
 *
 * @author Ævar Arnfjörð Bjarmason <avarab@gmail.com>
 * @copyright Copyright © 2005, Ævar Arnfjörð Bjarmason
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */

$wgExtensionFunctions[] = 'wfSpecialEditcount';
$wgExtensionCredits['specialpage'][] = array(
	'name' => 'Editcount',
	'version' => '1.1',
	'url' => 'http://www.mediawiki.org/wiki/Extension:Editcount',
	'author' => 'Ævar Arnfjörð Bjarmason',
	'description' => 'Displays [[Special:Editcount|edit count]] of a user',
);

function wfSpecialEditcount() {
	global $IP, $wgMessageCache;

	require_once ('SpecialEditcount.i18n.php' );
	foreach( efSpecialEditcountMessages() as $lang => $messages )
		$wgMessageCache->addMessages( $messages, $lang );

	$GLOBALS['wgAutoloadClasses']['Editcount'] = dirname( __FILE__ ) .
		'/SpecialEditcount_body.php';

	$GLOBALS['wgSpecialPages']['editcount'] = array( /*class*/ 'Editcount',
		/*name*/ 'Editcount', /* permission */'', /*listed*/ true,
		/*function*/ false, /*file*/ false );

}
