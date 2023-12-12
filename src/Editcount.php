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

		$dbr = MediaWikiServices::getInstance()->getDBLoadBalancerFactory()->getReplicaDatabase();
		$actorNormalization = MediaWikiServices::getInstance()->getActorNormalization();
		$actorId = $actorNormalization->findActorId( $user, $dbr );
		$query = $dbr->newSelectQueryBuilder()
			->caller( __METHOD__ )
			->select( [ 'page_namespace', 'count' => 'COUNT(*)' ] )
			->from( 'revision' )
			->join( 'page', null, 'page_id = rev_page' )
			->join( 'actor', null, 'rev_actor = actor_id' )
			->where( [ "actor_id" => $actorId ] )
			->groupBy( 'page_namespace' );
		$res = $query->fetchResultSet();

		$nsCount = [];
		foreach ( $res as $row ) {
			$nsCount[$row->page_namespace] = (int)$row->count;
		}
		return $nsCount;
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

		$dbr = MediaWikiServices::getInstance()->getDBLoadBalancerFactory()->getReplicaDatabase();
		$actorNormalization = MediaWikiServices::getInstance()->getActorNormalization();
		$actorId = $actorNormalization->findActorId( $user, $dbr );
		$query = $dbr->newSelectQueryBuilder()
			->caller( __METHOD__ )
			->select( 'COUNT(*)' )
			->from( 'revision' )
			->join( 'page', null, 'page_id = rev_page' )
			->join( 'actor', null, 'rev_actor = actor_id' )
			->where( [ "actor_id" => $actorId, "page_namespace" => $ns ] );
		return $query->fetchField();
	}
}
