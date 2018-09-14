<?php

class Editcount extends IncludableSpecialPage {
	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct( 'Editcount' );
	}

	/**
	 * Show the special page
	 *
	 * @param string|null $par User name, optionally followed by a namespace, or null
	 */
	public function execute( $par ) {
		global $wgContLang;

		$target = isset( $par ) ? $par : $this->getRequest()->getText( 'username' );

		list( $username, $namespace ) = $this->extractParamaters( $target );
		$this->getOutput()->enableOOUI();

		$user = User::newFromName( $username );
		$username = is_object( $user ) ? $user->getName() : '';

		$uid = ( $user instanceof User ? $user->getId() : 0 );

		if ( $this->including() ) {
			if ( $namespace === null ) {
				if ( $uid != 0 ) {
					$out = $wgContLang->formatNum( $user->getEditCount() );
				} else {
					$out = '';
				}
			} else {
				$out = $wgContLang->formatNum( $this->editsInNs( $uid, $namespace ) );
			}
			$this->getOutput()->addHTML( $out );
		} else {
			if ( $uid != 0 ) {
				$nscount = $this->editsByNs( $uid );
				$total = $this->getTotal( $nscount );
			}
			$html = new EditcountHTML;
			$html->setContext( $this->getContext() );
			// @fixme don't use @
			$html->outputHTML( $username, $uid, @$nscount, @$total );
		}
	}

	/**
	 * Parse the username and namespace parts of the input and return them
	 *
	 * @access private
	 *
	 * @param string $par
	 * @return array
	 */
	function extractParamaters( $par ) {
		global $wgContLang;

		// @fixme don't use @
		@list( $user, $namespace ) = explode( '/', $par, 2 );

		// str*cmp sucks
		if ( isset( $namespace ) ) {
			$namespace = $wgContLang->getNsIndex( $namespace );
		}

		return [ $user, $namespace ];
	}

	/**
	 * Compute and return the total edits in all namespaces
	 *
	 * @access private
	 *
	 * @param array $nscount An associative array
	 * @return int
	 */
	function getTotal( $nscount ) {
		$total = 0;
		foreach ( array_values( $nscount ) as $i ) {
			$total += $i;
		}

		return $total;
	}

	/**
	 * Count the number of edits of a user by namespace
	 *
	 * @param int $uid The user ID to check
	 * @return array
	 */
	function editsByNs( $uid ) {
		$nscount = [];

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

		foreach ( $res as $row ) {
			$nscount[$row->page_namespace] = $row->count;
		}

		return $nscount;
	}

	/**
	 * Count the number of edits of a user in a given namespace
	 *
	 * @param int $uid The user ID to check
	 * @param int $ns The namespace to check
	 * @return string
	 */
	function editsInNs( $uid, $ns ) {
		$dbr = wfGetDB( DB_REPLICA );
		$res = $dbr->selectField(
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

		return $res;
	}
}

class EditcountHTML extends Editcount {
	/**
	 * @var array
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
	 * @param array $nscount
	 * @param int $total
	 */
	function outputHTML( $username, $uid, $nscount, $total ) {
		$this->nscount = $nscount;
		$this->total = $total;

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
	 * @access private
	 */
	function makeTable() {
		$lang = $this->getLanguage();

		$total = $this->msg( 'editcount_total' )->escaped();
		$ftotal = $lang->formatNum( $this->total );
		$percent = $this->total > 0 ? wfPercent( $this->total / $this->total * 100, 2 ) : wfPercent( 0 ); // @bug 4400
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
