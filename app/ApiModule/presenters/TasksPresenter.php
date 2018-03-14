<?php

namespace ApiModule;

/**
 * TasksPresenter is used for serving list of task in experiment from REST API
 */
class TasksPresenter extends BasePresenter {

	private $tasksModel;
	private $experimentsModel;

	public function __construct( \Nette\Http\Request $httpRequest, \Tasks $tasksModel, \Experiments $experimentsModel ) {
		parent::__construct( $httpRequest );
		$this->tasksModel = $tasksModel;
		$this->experimentsModel = $experimentsModel;
	}

	public function renderDefault( $experimentId ) {
		$parameters = $this->context->getParameters();
		$show_administration = $parameters[ "show_administration" ];

		$response = array();
		$response[ 'tasks' ] = array();
		foreach( $this->tasksModel->getTasks( $experimentId ) as $task ) {
			$taskResponse[ 'id' ] = $task->id;
			$taskResponse[ 'name' ] = $task->name;
			$taskResponse[ 'description' ] = $task->description;
			if( $show_administration ) {
				$taskResponse[ 'edit_link' ] = $this->link( ':Tasks:edit', $task->id );
				$taskResponse[ 'delete_link' ] = $this->link( ':Tasks:delete', $task->id );
			}

			$response[ 'tasks' ][ $task->id ] = $taskResponse;
		}

		$response[ 'show_administration' ] = $show_administration;

		$this->sendResponse( new \Nette\Application\Responses\JsonResponse( $response ) );
	}

	public function renderUpload() {
		$name = $this->getPostParameter( 'name' );
		$url_key = \Nette\Utils\Strings::webalize( $name );
		$description = $this->getPostParameter( 'description' );
		$experiment_id = $this->getPostParameter( 'experiment_id', false );
		$experiment_name = $this->getPostParameter( 'experiment_name', false );
		$experiment_desc = $this->getPostParameter( 'experiment_description', false );
		$experiment_url_key = \Nette\Utils\Strings::webalize( $experiment_name );
		$translation = $this->getPostFile( 'translation' );

		if($experiment_id) {
			// Look up experiment by Id
			$experiment = $this->experimentsModel->getExperimentById( $experiment_id );
		}
		if( $experiment_name ) {
			// Lookup experiment by Name
			$experiment = $this->experimentsModel->getExperimentByName( $experiment_url_key );
			if( !$experiment ) {
				Debugger::log("Experiment not found, creating", Debugger::INFO);
				// Create new experiment
				$data = array(
					'name' => $experiment_name,
					'description' => $this->getPostParameter( 'description' ),
					'url_key' => $experiment_url_key
				);
				$source = $this->getPostFile( 'source' );
				$reference = $this->getPostFile( 'reference' );

				$path = __DIR__ . '/../../../data/' . $experiment_url_key . '/';
				$source->move( $path . 'source.txt' );
				$reference->move( $path . 'reference.txt' );
				file_put_contents( $path . 'config.neon', "name: $experiment_name\ndescription: $experiment_desc\nurl_key: $experiment_url_key" );

				$experiment_id = $this->experimentsModel->saveExperiment( $data );

				sleep(10); // wait for import

			} else {
				$experiment_id = $experiment->id;
			}
		}

		$path = __DIR__ . '/../../../data/' . $experiment_url_key. '/' . $url_key . '/';
		if( file_exists( $path ) ) {
			return $this->sendResponse( new \Nette\Application\Responses\JsonResponse( array( 'error' => "Tasks exists $url_key" ) ) );
		} else {
			$translation->move( $path . 'translation.txt' );
			file_put_contents( $path . 'config.neon', "name: $name\ndescription: $description\nurl_key: $url_key" );

			$data = array(
				'name' => $name,
				'description' => $description,
				'url_key' => $url_key,
				'experiments_id' => $experiment_id
			);

			$response = array( 'task_id' => $this->tasksModel->saveTask( $data ) );

			if ( $this->getPostParameter( 'redirect', False ) ) {
				$this->flashMessage( "Task was successfully uploaded. It will appear in this list once it is imported.", "success" );
				$this->redirect( ":Tasks:list", $experiment_id );
			} else {
				$this->sendResponse( new \Nette\Application\Responses\JsonResponse( $response ) );
			}
		}
	}

}
