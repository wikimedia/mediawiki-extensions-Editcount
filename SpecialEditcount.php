<?php
if (!defined('MEDIAWIKI')) die();
/**
 * A Special Page extension that displays edit counts.
 *
 * This page can be accessed from Special:Editcount[/user] as well as being
 * included like {{Special:Editcount/user}}
 *
 * @package MediaWiki
 * @subpackage Extensions
 *
 * @author Ævar Arnfjörð Bjarmason <avarab@gmail.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */

$wgExtensionFunctions[] = 'wfSpecialEditcount';
$wgExtensionCredits['specialpage'][] = array(
	'name' => 'Editcount',
	'author' => 'Ævar Arnfjörð Bjarmason',
);

function wfSpecialEditcount() {
	global $IP, $wgMessageCache;
	
	$wgMessageCache->addMessages(
		array(
			'editcount' => 'Edit count',
			'editcount_username' => 'User: ',
			'editcount_total' => 'Total',
		)
	);

	require_once( "$IP/includes/SpecialPage.php" );
	class Editcount extends SpecialPage {
		function Editcount() {
			SpecialPage::SpecialPage( 'Editcount' );
			$this->includable( true );
		}
		
		function execute( $par = null ) {
			global $wgOut, $wgRequest, $wgContLang;

			$username = isset( $par ) ? $par : $wgRequest->getText( 'username' );
			$username = strtr( $wgContLang->ucfirst( $username ), '_', ' ' );
			
			$total = 0;
			$nscount = $this->editsByNs( User::idFromName( $username ) );
			foreach ( $nscount as $ns => $edits )
				$total += $edits;
			
			if ( $this->including() ) {
				$wgOut->addHTML( $wgContLang->formatNum( $total ) );
			} else {
				global $wgLang, $wgTitle, $wgVersion;
			
				$this->setHeaders();
				if ( version_compare( $wgVersion, '1.5beta4', '<' ) ) {
					$wgOut->versionRequired( '1.5beta4' );
					return;
				}

				$action = $wgTitle->escapeLocalUrl();
				$user = wfMsgHtml( 'editcount_username' );
				$go = wfMsgHtml( 'go' );
				$wgOut->addHTML( "
<form id='editcount' method='post' action=\"$action\">
	<label>
		$user
		<input tabindex='1' type='text' size='20' name='username' value=\"" . htmlspecialchars( $username ) . "\"/>
	</label>
	<input type='submit' name='submit' value=\"$go\"/>
</form>");
				if ($username == '')
					return;
			
				$out = '<p><table border="2" cellpadding="4" cellspacing="0" style=";margin: 1em 1em 1em 0; background: #fff; border: 1px #aaa solid; border-collapse: collapse; font-size: 95%;">';
				$out .= '<tr><th>' .
					wfMsg( 'editcount_total' ) .
					"</th><th>$total</th><th>" .
					wfPercent( $total / $total * 100 , 2 ) .
					'</th></tr>';
				foreach( $nscount as $ns => $edits ) {
					$fns = $ns == NS_MAIN ? wfMsg( 'blanknamespace' ) : $wgLang->getFormattedNsText( $ns );
					$percent = wfPercent( $edits / $total * 100 );
					$out .= "<tr><td>$fns</td><td>$edits</td><td>$percent</td></tr>";
				}
				$out .= '</table></p>';
				$wgOut->addHTML( $out );
			}
		}
		
		/**
		 * Count the number of edits of a user by namespace
		 *
		 * @param int $uid The user ID to check
		 * @return array
		 */
		function editsByNs( $uid ) {
			$fname = 'Editcount::editsByNs';
			$nscount = array();

			$dbr =& wfGetDB( DB_SLAVE );
			$res = $dbr->select(
				array( 'user', 'revision', 'page' ),
				array( 'page_namespace', 'COUNT(*) as count' ),
				array(
					'user_id' => $uid,
					'rev_user = user_id',
					'rev_page = page_id'
				),
				$fname,
				array( 'GROUP BY' => 'page_namespace' )
			);

			while( $row = $dbr->fetchObject( $res ) ) {
				$nscount[$row->page_namespace] = $row->count;
			}

			return $nscount;
		}
	}
	
	SpecialPage::addPage( new Editcount );
}
