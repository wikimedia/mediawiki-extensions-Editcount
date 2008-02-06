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

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'Editcount',
	'version' => '2008-01-11',
	'author' => 'Ævar Arnfjörð Bjarmason',
	'description' => 'Displays [[Special:Editcount|edit count]] of a user',
	'descriptionmsg' => 'editcount-desc',
	'url' => 'http://www.mediawiki.org/wiki/Extension:Editcount',
);

$dir = dirname(__FILE__) . '/';
$wgExtensionMessagesFiles['Editcount'] = $dir .'SpecialEditcount.i18n.php';
$wgAutoloadClasses['Editcount'] = $dir . 'SpecialEditcount_body.php';
$wgSpecialPages['Editcount'] = 'Editcount';
