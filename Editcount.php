<?php
/**
 * A Special Page extension that displays edit counts.
 *
 * This page can be accessed from Special:Editcount[/user] as well as being
 * included like {{Special:Editcount/user[/namespace]}}
 *
 * @file
 * @ingroup Extensions
 * @author Ævar Arnfjörð Bjarmason <avarab@gmail.com>
 * @copyright Copyright © 2005, Ævar Arnfjörð Bjarmason
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */

if ( !defined( 'MEDIAWIKI' ) ) {
	die();
}

$wgExtensionCredits['specialpage'][] = array(
	'path' => __FILE__,
	'name' => 'Editcount',
	'author' => 'Ævar Arnfjörð Bjarmason',
	'descriptionmsg' => 'editcount-desc',
	'url' => 'https://www.mediawiki.org/wiki/Extension:Editcount',
);

$wgMessagesDirs['Editcount'] = __DIR__ . '/i18n';
$wgExtensionMessagesFiles['Editcount'] = __DIR__ . '/Editcount.i18n.php';
$wgExtensionMessagesFiles['EditcountAliases'] = __DIR__ . '/Editcount.alias.php';
$wgAutoloadClasses['Editcount'] = __DIR__ . '/Editcount_body.php';
$wgAutoloadClasses['EditcountHTML'] = __DIR__ . '/Editcount_body.php';
$wgSpecialPages['Editcount'] = 'Editcount';
