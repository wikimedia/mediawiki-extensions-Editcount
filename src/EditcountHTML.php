<?php

use MediaWiki\Html\Html;

class EditcountHTML extends Editcount {
	/** @var int[] */
	private array $nscount;

	/** @var int */
	private int $total;

	/**
	 * Output the HTML form on Special:Editcount
	 *
	 * @param string $username
	 * @param int $uid
	 * @param int[] $nscount
	 * @param int|null $total
	 */
	public function outputHTML( string $username, int $uid, array $nscount, ?int $total = null ): void {
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
	private function makeTable(): string {
		$lang = $this->getLanguage();

		$ret = Html::openElement( 'table', [
			'class' => 'wikitable',
			'style' => 'text-align: center;',
		] );

		$ret .= Html::rawElement( 'tr', [],
			Html::element( 'th', [], $this->msg( 'editcount_namespace' )->text() ) .
			Html::element( 'th', [], $this->msg( 'editcount_edits' )->text() ) .
			Html::element( 'th', [], $this->msg( 'editcount_percentage' )->text() )
		);

		foreach ( $this->nscount as $ns => $edits ) {
			if ( $ns === NS_MAIN ) {
				$fns = $this->msg( 'blanknamespace' )->text();
			} else {
				$fns = $lang->getFormattedNsText( $ns ) ?: $ns;
			}
			$ret .= Html::rawElement( 'tr', [],
				Html::element( 'td', [], $fns ) .
				Html::element( 'td', [], $lang->formatNum( $edits ) ) .
				Html::element( 'td', [], $this->msg( 'percent' )
					->numParams( round( $edits / $this->total * 100, 2 ) )
					->text() )
			);
		}

		$ret .= Html::rawElement( 'tr', [],
			Html::element( 'th', [], $this->msg( 'editcount_total' )->text() ) .
			Html::element( 'td', [], $lang->formatNum( $this->total ) ) .
			Html::element( 'td', [], $this->msg( 'percent' )
				->numParams( $this->total ? 100 : 0 )
				->text() )
		);

		$ret .= Html::closeElement( 'table' );

		return $ret;
	}
}
