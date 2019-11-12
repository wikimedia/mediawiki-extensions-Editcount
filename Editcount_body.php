<?php

use MediaWiki\MediaWikiServices;

class Editcount extends IncludableSpecialPage {
	public function __construct() {
		parent::__construct( 'Editcount' );
	}

	/**
	 * Show the special page
	 *
	 * @param string|null $par User name, optionally followed by a namespace, or null
	 */
	public function execute( $par ) {
		$target = isset( $par ) ? $par : $this->getRequest()->getText( 'username' );

		list( $username, $namespace ) = $this->extractParameters( $target );
		$this->getOutput()->enableOOUI();

		$user = User::newFromName( $username );
		$username = $user ? $user->getName() : '';
		$uid = $user ? $user->getId() : 0;

		if ( $this->including() ) {
			$contLang = MediaWikiServices::getInstance()->getContentLanguage();
			if ( $namespace === null ) {
				if ( $uid != 0 ) {
					$out = $contLang->formatNum( $user->getEditCount() );
				} else {
					$out = '';
				}
			} else {
				$out = $contLang->formatNum( $this->editsInNs( $uid, $namespace ) );
			}
			$this->getOutput()->addHTML( $out );
		} else {
			$nscount = $this->editsByNs( $uid );
			$html = new EditcountHTML;
			$html->setContext( $this->getContext() );
			$html->outputHTML( $username, $uid, $nscount );
		}
	}

	/**
	 * Parse the username and namespace parts of the input and return them
	 *
	 * @param string $par
	 * @return array
	 */
	private function extractParameters( $par ) {
		$parts = explode( '/', $par, 2 );
		$parts[1] = isset( $parts[1] )
			? MediaWikiServices::getInstance()->getContentLanguage()->getNsIndex( $parts[1] )
			: null;
		return $parts;
	}

	/**
	 * Count the number of edits of a user by namespace
	 *
	 * @param int $uid The user ID to check
	 * @return int[]
	 */
	protected function editsByNs( $uid ) {
		if ( $uid <= 0 ) {
			return [];
		}

		$dbr = wfGetDB( DB_REPLICA );
		$res = $dbr->select(
			[ 'user', 'revision', 'page' ],
			[ 'page_namespace', 'COUNT(*) AS count' ],
			[
				'user_id' => $uid,
				'rev_user = user_id',
				'rev_page = page_id'
			],
			__METHOD__,
			[ 'GROUP BY' => 'page_namespace' ]
		);

		$nscount = [];
		foreach ( $res as $row ) {
			$nscount[$row->page_namespace] = (int)$row->count;
		}
		return $nscount;
	}

	/**
	 * Count the number of edits of a user in a given namespace
	 *
	 * @param int $uid The user ID to check
	 * @param int $ns The namespace to check
	 * @return int
	 */
	protected function editsInNs( $uid, $ns ) {
		if ( $uid <= 0 ) {
			return 0;
		}

		$dbr = wfGetDB( DB_REPLICA );
		return (int)$dbr->selectField(
			[ 'user', 'revision', 'page' ],
			[ 'COUNT(*) AS count' ],
			[
				'user_id' => $uid,
				'page_namespace' => $ns,
				'rev_user = user_id',
				'rev_page = page_id'
			],
			__METHOD__,
			[ 'GROUP BY' => 'page_namespace' ]
		);
	}
}

class EditcountHTML extends Editcount {
	/**
	 * @var int[]
	 */
	private $nscount;

	/**
	 * @var int
	 */
	private $total;

	/**
	 * Output the HTML form on Special:Editcount
	 *
	 * @param string $username
	 * @param int $uid
	 * @param int[] $nscount
	 * @param int|null $total
	 */
	public function outputHTML( $username, $uid, array $nscount, $total = null ) {
		$this->nscount = $nscount;
		$this->total = $total ?: array_sum( $nscount );

		$this->setHeaders();

		$action = htmlspecialchars( $this->getPageTitle()->getLocalURL() );
		$user = $this->msg( 'editcount_username' )->escaped();
		$out = "
		<form id='editcount' method='post' action=\"$action\">
			<table>
				<tr>
					<td>$user</td>
					<td>" . new OOUI\TextInputWidget( [
						'name' => 'username',
						'value' => $username,
						'autofocus' => true,
					] ) . "</td>
					<td>" . new OOUI\ButtonInputWidget( [
						'label' => $this->msg( 'editcount_submit' )->text(),
						'flags' => [ 'primary', 'progressive' ],
						'type' => 'submit',
					] ) . " </td>
				</tr>";
		if ( $username != null && $uid != 0 ) {
			$editcounttable = $this->makeTable();
			$out .= "
				<tr>
					<td>&#160;</td>
					<td>$editcounttable</td>
					<td>&#160;</td>
				</tr>";
		}
		$out .= '
			</table>
		</form>';
		$this->getOutput()->addHTML( $out );
	}

	/**
	 * Make the editcount-by-namespaces HTML table
	 *
	 * @return string
	 */
	private function makeTable() {
		$lang = $this->getLanguage();

		$total = $this->msg( 'editcount_total' )->escaped();
		$ftotal = $lang->formatNum( $this->total );
		$percent = wfPercent( $this->total ? 100 : 0 );
		// @fixme don't use inline styles
		$ret = "<table border='1' style='background-color: #fff; border: 1px #aaa solid; border-collapse: collapse;'>
				<tr>
					<th>$total</th>
					<th>$ftotal</th>
					<th>$percent</th>
				</tr>
		";

		foreach ( $this->nscount as $ns => $edits ) {
			$fedits = $lang->formatNum( $edits );
			$fns = ( $ns == NS_MAIN ) ? $this->msg( 'blanknamespace' ) : $lang->getFormattedNsText( $ns );
			$percent = wfPercent( $edits / $this->total * 100 );
			$fpercent = $lang->formatNum( $percent );
			$ret .= "
				<tr>
					<td>$fns</td>
					<td>$fedits</td>
					<td>$fpercent</td>
				</tr>
			";
		}
		$ret .= '</table>
		';

		return $ret;
	}
}
