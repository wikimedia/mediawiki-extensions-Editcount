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
		$target = $par ?? $this->getRequest()->getText( 'username' );

		list( $username, $namespace ) = $this->extractParameters( $target );
		$this->getOutput()->enableOOUI();
		$this->getOutput()->addWikiMsg( 'editcount-before' );

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
				$out = $contLang->formatNum( $this->editsInNs( $user, $namespace ) );
			}
			// @phan-suppress-next-line SecurityCheck-XSS
			$this->getOutput()->addHTML( $out );
		} else {
			$nscount = $this->editsByNs( $user );
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
	 * @param User $user The user to check
	 * @return int[]
	 */
	protected function editsByNs( $user ) {
		if ( !$user || $user->getId() <= 0 ) {
			return [];
		}

		$dbr = wfGetDB( DB_REPLICA );
		$actorWhere = ActorMigration::newMigration()->getWhere( $dbr, 'rev_user', $user );
		$res = $dbr->select(
			[ 'revision', 'page' ] + $actorWhere['tables'],
			[ 'page_namespace', 'COUNT(*) AS count' ],
			[ $actorWhere['conds'] ],
			__METHOD__,
			[ 'GROUP BY' => 'page_namespace' ],
			[ 'page' => [ 'JOIN', 'page_id = rev_page' ] ] + $actorWhere['joins']
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
	 * @param User $user The user to check
	 * @param int $ns The namespace to check
	 * @return int
	 */
	protected function editsInNs( $user, $ns ) {
		if ( !$user || $user->getId() <= 0 ) {
			return 0;
		}

		$dbr = wfGetDB( DB_REPLICA );
		$actorWhere = ActorMigration::newMigration()->getWhere( $dbr, 'rev_user', $user );
		return (int)$dbr->selectField(
			[ 'revision', 'page' ] + $actorWhere['tables'],
			'COUNT(*)',
			[ 'page_namespace' => $ns, $actorWhere['conds'] ],
			__METHOD__,
			[],
			[ 'page' => [ 'JOIN', 'page_id = rev_page' ] ] + $actorWhere['joins']
		);
	}
}
