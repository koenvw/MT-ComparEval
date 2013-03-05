<?php

class TaskListEntry {

	private $node;

	public function __construct( $node ) {
		$this->node = $node;
	}


	public function getMetric( $name ) {
		$metrics = $this->node->find( 'css', '.metrics' );

		return $metrics->find( 'xpath', "//dd[@data-metric='$name']" )->getText();
	}

}
